<?php

interface IActionView
{
    public function onResponse(
        AbstractAction &$action, Response &$response, ?array &$vars
    ): bool;

}
