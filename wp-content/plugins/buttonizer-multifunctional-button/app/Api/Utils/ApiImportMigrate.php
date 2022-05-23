<?php
/* 
 * SOFTWARE LICENSE INFORMATION
 * 
 * Copyright (c) 2017 Buttonizer, all rights reserved.
 * 
 * This file is part of Buttonizer
 * 
 * For detailed information regarding to the licensing of
 * this software, please review the license.txt or visit:
 * https://buttonizer.pro/license/
 */

namespace Buttonizer\Api\Utils;

use Buttonizer\Utils\Maintain;
use Buttonizer\Utils\Update;

/**
 * Dashboard API
 * 
 * @endpoint /wp-json/buttonizer/import_migrate
 * @methods GET
 */
class ApiImportMigrate {
    /**
     * Register route
     */
    public function registerRoute() {
        register_rest_route('buttonizer', '/import_migrate', [
            [
                'methods'  => ['POST'],
                'args' => [
                    'data' => [
                        'required' => true,
                        "type" => "object"
                    ],
                ],
                'callback' => [$this, 'migrate'],
                'permission_callback' => function() {
                    return Maintain::hasPermission();
                }
            ]
        ]);
    }

    /**
     * Get settings
     */
    public function migrate($request) {
        $data = json_decode($request->get_body(), true);

        // return $data;
        if(!isset($data['data'])) {
            return [
                'success' => false,
                'results' => [],
            ];
        }
        if($data['data']['export_type'] === "button") {
            return [
                'success' => true,
                'results' => $data['data'],
            ];
        } 
        
        return [
            'success' => true,
            'results' => (new Update())->migration5UpdateData([$data['data']])[0],
        ];
    }
}