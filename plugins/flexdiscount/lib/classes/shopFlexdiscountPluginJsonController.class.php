<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginJsonController extends waJsonController
{
    protected function preExecute()
    {
        // Google bot fix
        if (!waRequest::isXMLHttpRequest()) {
            $this->getResponse()->addHeader('Content-type', 'application/json');
        }
        $this->getResponse()->addHeader('X-Robots-Tag', 'none');
    }
}