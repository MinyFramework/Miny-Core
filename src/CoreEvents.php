<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny;

class CoreEvents
{
    const FILTER_REQUEST      = 'filter_request';
    const FILTER_RESPONSE     = 'filter_response';
    const UNCAUGHT_EXCEPTION  = 'onUncaughtException';
    const BEFORE_RUN          = 'onBeforeRun';
    const CONTROLLER_LOADED   = 'onControllerLoaded';
    const CONTROLLER_FINISHED = 'onControllerFinished';
    const SHUTDOWN            = 'onShutdown';
}
