<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

class CoreEvents
{
    const FILTER_REQUEST      = 'filter_request';
    const FILTER_RESPONSE     = 'filter_response';
    const UNCAUGHT_EXCEPTION  = 'uncaught_exception';
    const BEFORE_RUN          = 'before_run';
    const CONTROLLER_LOADED   = 'onControllerLoaded';
    const CONTROLLER_FINISHED = 'onControllerFinished';
}
