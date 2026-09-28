<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi Permission & Role Governance Service.
 *
 * Supports:
 * - Granular Actions: view, add/create, edit, delete, approve, export, manage, refund, override, execute
 * - Granular Scopes: own, assigned, branch, all
 * - User -> Role -> Permission -> Scope -> Branch resolution
 * - Seamless backward compatibility with legacy bitmasks
 */
class Permission_service
{
    protected CI_Controller $CI;

    /** Scope ranking for comparison */
    private const SCOPE_HIERARCHY = [
        'none' => 0,
        'own' => 1,
        'assigned' => 2,
        'branch' => 3,
        'all' => 4,
    ];

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('roles_model');
        $this->CI->load->model('users_model');
    }

    /**
     * Check if a user can perform an action on a resource with an optional scope requirement.
     *
     * @param string $action Action (view, add, edit, delete, approve, export, manage, refund, override, execute)
     * @param string $resource Resource name or module (appointments, customers, services, etc.)
     * @param int|null $user_id User ID (null for currently logged in user)
     * @param string|null $required_scope Required minimum scope (own, assigned, branch, all)
     * @param array $context Context details (e.g. ['branch_id' => 1, 'provider_id' => 5, 'customer_id' => 10])
     * @return bool
     */
    public function can(
        string $action,
        string $resource,
        ?int $user_id = null,
        ?string $required_scope = null,
        array $context = []
    ): bool {
        // Alias normalization
        if ($action === 'create') {
            $action = 'add';
        }

        $user_id = $user_id ?: (int) session('user_id');
        $role_slug = $this->get_user_role_slug($user_id);

        if (!$user_id && empty($role_slug)) {
            return false;
        }

        if (empty($role_slug)) {
            return false;
        }

        // 1. Owner & Superadmin bypass (Always granted full permissions across all scopes)
        if ($role_slug === 'admin' || $role_slug === 'owner' || (session('is_admin') && $role_slug !== 'kitchen' && $role_slug !== 'waiter')) {
            return true;
        }

        // 2. Fetch role record
        $role = $this->CI->db->get_where('roles', ['slug' => $role_slug])->row_array();
        if (!$role) {
            return false;
        }

        // 3. Check Granular JSON Permissions
        if (!empty($role['permissions_json'])) {
            $perms = json_decode($role['permissions_json'], true);
            if (is_array($perms)) {
                // Wildcard role: e.g. general_manager has ['*' => ['*' => 'all']]
                if (isset($perms['*']['*'])) {
                    return true;
                }

                if (isset($perms[$resource])) {
                    $resource_perms = $perms[$resource];
                    if (isset($resource_perms['*'])) {
                        return true;
                    }
                    if (isset($resource_perms[$action])) {
                        $granted_scope = $resource_perms[$action];
                        if ($granted_scope === true) {
                            $granted_scope = 'all';
                        } elseif ($granted_scope === false || $granted_scope === 'none') {
                            return false;
                        }

                        // If no specific scope was requested, having the action granted is sufficient
                        if ($required_scope === null) {
                            return true;
                        }

                        return $this->evaluate_scope($granted_scope, $required_scope, $user_id, $context);
                    }
                }

                // If role has explicit permissions_json configured, anything not granted is DENIED
                return false;
            }
        }

        // 4. Fallback to Legacy Bitmask CRUD
        $legacy_perms = $this->CI->roles_model->get_permissions_by_slug($role_slug);
        if (isset($legacy_perms[$resource][$action])) {
            $allowed = (bool) $legacy_perms[$resource][$action];
            if (!$allowed) {
                return false;
            }

            if ($required_scope === null) {
                return true;
            }

            // Assign default scope based on legacy role archetype
            $default_scope = match ($role_slug) {
                'admin', 'owner' => 'all',
                'secretary', 'manager', 'reception', 'cashier' => 'branch',
                'provider', 'doctor', 'professional', 'therapist', 'trainer', 'waiter' => 'assigned',
                default => 'own',
            };

            return $this->evaluate_scope($default_scope, $required_scope, $user_id, $context);
        }

        return false;
    }

    /**
     * Check whether a granted scope satisfies the required scope and branch/user context.
     */
    public function evaluate_scope(string $granted_scope, string $required_scope, int $user_id, array $context = []): bool
    {
        $granted_rank = self::SCOPE_HIERARCHY[$granted_scope] ?? 0;
        $required_rank = self::SCOPE_HIERARCHY[$required_scope] ?? 0;

        if ($granted_rank < $required_rank) {
            return false;
        }

        // Branch boundary check: if a specific branch is accessed, ensure user is authorized
        if (isset($context['branch_id']) && $granted_scope !== 'all') {
            $target_branch = (int) $context['branch_id'];
            $user_branches = $this->get_user_branch_ids($user_id);
            if (!empty($user_branches) && !in_array($target_branch, $user_branches, true)) {
                return false;
            }
        }

        // Assigned boundary check
        if ($granted_scope === 'assigned' && isset($context['provider_id'])) {
            if ((int) $context['provider_id'] !== $user_id) {
                return false;
            }
        }

        // Own boundary check
        if ($granted_scope === 'own' && isset($context['user_id'])) {
            if ((int) $context['user_id'] !== $user_id) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get authorized branch IDs for a user.
     *
     * @param int $user_id
     * @return array
     */
    public function get_user_branch_ids(int $user_id): array
    {
        static $cached_branches = [];

        if (isset($cached_branches[$user_id])) {
            return $cached_branches[$user_id];
        }

        $branch_ids = [];

        // Check user_branches table first
        if ($this->CI->db->table_exists('user_branches')) {
            $rows = $this->CI->db->select('branch_id')->where('user_id', $user_id)->get('user_branches')->result_array();
            foreach ($rows as $r) {
                $branch_ids[] = (int) $r['branch_id'];
            }
        }

        // Check users.branch_ids or id_branches
        if (empty($branch_ids)) {
            $user = $this->CI->db->select('id_branches, branch_ids')->where('id', $user_id)->get('users')->row_array();
            if ($user) {
                if (!empty($user['branch_ids'])) {
                    $decoded = json_decode($user['branch_ids'], true);
                    if (is_array($decoded)) {
                        $branch_ids = array_map('intval', $decoded);
                    } else {
                        $branch_ids = array_map('intval', explode(',', $user['branch_ids']));
                    }
                } elseif (!empty($user['id_branches'])) {
                    $branch_ids[] = (int) $user['id_branches'];
                }
            }
        }

        $branch_ids = array_unique(array_filter($branch_ids));
        $cached_branches[$user_id] = $branch_ids;
        return $branch_ids;
    }

    /**
     * Check if module permission is granted for user.
     */
    public function has_module_permission(string $module, ?int $user_id = null): bool
    {
        return $this->can('view', $module, $user_id);
    }

    /**
     * Check if user has access to a specific branch.
     *
     * @param int $user_id
     * @param int $branch_id
     * @return bool
     */
    public function check_branch_access(int $user_id, int $branch_id): bool
    {
        $role_slug = $this->get_user_role_slug($user_id);
        if (in_array($role_slug, ['admin', 'owner'], true)) {
            return true;
        }

        $user_branches = $this->get_user_branch_ids($user_id);
        if (empty($user_branches)) {
            return true;
        }

        return in_array($branch_id, $user_branches, true);
    }

    /**
     * Resolve the role slug for a user or the current session.
     */
    public function get_user_role_slug(?int $user_id = null): ?string
    {
        $session_role = session('role_slug');
        $current_user_id = (int) session('user_id');

        if ($user_id === null || $user_id === $current_user_id) {
            if (!empty($session_role)) {
                return (string) $session_role;
            }
        }

        $target_id = $user_id ?: $current_user_id;
        if ($target_id > 0) {
            $user = $this->CI->db->select('role_slug, id_roles')->where('id', $target_id)->get('users')->row_array();
            if ($user) {
                if (!empty($user['role_slug'])) {
                    return $user['role_slug'];
                }
                if (!empty($user['id_roles'])) {
                    return $this->CI->roles_model->value((int) $user['id_roles'], 'slug');
                }
            }
        }

        return !empty($session_role) ? (string) $session_role : null;
    }
}


