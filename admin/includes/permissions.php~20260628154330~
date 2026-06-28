<?php
/**
 * Permission utility functions for admin panel
 */

/**
 * Check if the current user has the specified permission
 *
 * @param string $permissionKey The permission key to check
 * @return bool True if the user has the permission, false otherwise
 */
function hasPermission($permissionKey) {
    global $conn;
    
    // Admin giriş yapmışsa tüm izinleri var varsay (basit güvenlik modeli)
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        return true;
    }
    
    // Tablolar mevcut değilse veya giriş yapılmamışsa false döndür
    return false;
}

/**
 * Get all permissions for a role
 *
 * @param int $roleId The role ID
 * @return array Permissions for the role
 */
function getRolePermissions($roleId) {
    global $conn;
    
    $permissions = [];
    
    $sql = "SELECT ap.id, ap.permission_key, ap.display_name, ap.module, ap.description
            FROM admin_permissions ap
            JOIN role_permissions rp ON ap.id = rp.permission_id
            WHERE rp.role_id = ?
            ORDER BY ap.module, ap.display_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row;
    }
    
    return $permissions;
}

/**
 * Get all available permissions
 *
 * @return array All permissions
 */
function getAllPermissions() {
    global $conn;
    
    $permissions = [];
    
    $sql = "SELECT id, permission_key, display_name, module, description
            FROM admin_permissions
            ORDER BY module, display_name";
    
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row;
    }
    
    return $permissions;
}

/**
 * Get all permissions grouped by module
 *
 * @return array Permissions grouped by module
 */
function getPermissionsByModule() {
    $permissions = getAllPermissions();
    $grouped = [];
    
    foreach ($permissions as $permission) {
        $module = $permission['module'];
        if (!isset($grouped[$module])) {
            $grouped[$module] = [];
        }
        $grouped[$module][] = $permission;
    }
    
    return $grouped;
}

/**
 * Check if the current user has access to a specific module
 *
 * @param string $module The module name
 * @return bool True if the user has access to the module
 */
function hasModuleAccess($module) {
    // Admin giriş yapmışsa tüm modüllere erişimi var
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        return true;
    }
    
    return false;
}

/**
 * Get all roles
 *
 * @return array All roles
 */
function getAllRoles() {
    global $conn;
    
    $roles = [];
    
    $sql = "SELECT id, role_name, description, created_at
            FROM admin_roles
            ORDER BY id";
    
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
    
    return $roles;
}

/**
 * Get a role by ID
 *
 * @param int $roleId The role ID
 * @return array|null The role or null if not found
 */
function getRoleById($roleId) {
    global $conn;
    
    $sql = "SELECT id, role_name, description, created_at
            FROM admin_roles
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

/**
 * Update login session data with role information
 */
function updateSessionWithRoleInfo() {
    // Şimdilik basit güvenlik modeli - giriş yapmış admin tüm yetkilere sahip
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $_SESSION['admin_role_id'] = 1; // Superadmin rolü
    }
}

// Check and update session with role information
updateSessionWithRoleInfo(); 