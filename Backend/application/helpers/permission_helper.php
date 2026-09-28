<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

if (!function_exists('can')) {
    /**
     * Check if a user can perform an action on a resource, with optional scope and context.
     *
     * Example:
     * if (can('edit', 'appointments') === FALSE) abort(403);
     * if (can('view', 'appointments', null, 'branch', ['branch_id' => 2]) === FALSE) abort(403);
     *
     * @param string $action Action (view, add, edit, delete, approve, export, manage, refund, override, execute)
     * @param string $resource Resource or module name
     * @param int|null $user_id Optional user ID (defaults to current logged-in user)
     * @param string|null $required_scope Optional scope: own, assigned, branch, all
     * @param array $context Context details (e.g. ['branch_id' => 1])
     * @return bool
     */
    function can(
        string $action,
        string $resource,
        ?int $user_id = null,
        ?string $required_scope = null,
        array $context = []
    ): bool {
        $CI = &get_instance();
        if (!isset($CI->permission_service)) {
            $CI->load->library('permission_service');
        }

        return $CI->permission_service->can($action, $resource, $user_id, $required_scope, $context);
    }
}

if (!function_exists('cannot')) {
    /**
     * Check if a user CANNOT perform an action on a resource.
     *
     * @param string $action
     * @param string $resource
     * @param int|null $user_id
     * @param string|null $required_scope
     * @param array $context
     * @return bool
     */
    function cannot(
        string $action,
        string $resource,
        ?int $user_id = null,
        ?string $required_scope = null,
        array $context = []
    ): bool {
        return !can($action, $resource, $user_id, $required_scope, $context);
    }
}
