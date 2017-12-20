<?php

/**
 * Controller extension that binds details of the configured interactives
 * into the current page view
 * 
 * @author marcus
 */
class InteractiveControllerExtension extends Extension
{
    public function onAfterInit() {
        Requirements::javascript(THIRDPARTY_DIR.'/jquery/jquery.js');
        Requirements::javascript('interactives/javascript/interactives.js');

        $url = $this->owner->getRequest()->getURL();

        $siteWide = InteractiveCampaign::get()->filter(['SiteWide' => 1]);
        
        $page = $this->owner->data();
        if ($page instanceof Page) {
            $pageCampaigns = InteractiveCampaign::get()->filterAny(['OnPages.ID' => $page->ID]);
        }

        $campaigns = array_merge($siteWide->toArray(), $pageCampaigns->toArray());

        $items = [];
        foreach ($campaigns as $campaign) {
            // collect its interactives.
            $anyViewable = $campaign->invokeWithExtensions('viewableOn', $url, $page ? $page->class : null);
            $canView = array_reduce($anyViewable, function ($carry, $item) {
                return $carry && $item;
            }, true);

            if (!$canView) {
                continue;
            }

            $interactives = $campaign->relevantInteractives($url, $page);
            $items[] = array(
                'interactives' => $interactives,
                'display'       => $campaign->DisplayType,
                'id'            => $campaign->ID,
                'trackIn'       => $campaign->TrackIn,
            );
        }

        $data = array(
            'endpoint'  => '',
            'trackviews'    => false,
            'trackclicks'   => true,
            'remember'      => false,
            'campaigns'     => $items,
            'tracker'       => Config::inst()->get('Interactive', 'tracker_type'),
        );

        $data = json_encode($data);
        Requirements::customScript('window.SSInteractives = {config: ' . $data . '};', 'ads');
    }
}