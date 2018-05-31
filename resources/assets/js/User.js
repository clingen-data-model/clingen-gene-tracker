class User {
    constructor(userData) {
        this.user = user
    }

    hasRole(roleName) {
        return this.user.roles.filter((role) => role.name == roleName).length > 0
    }

    hasPermission(permName) {
        return this.user.permissions.filter(permission => permission.name == permName).length > 0
    }

    inExpertPanel(expertPanel) {
        return this.user.expert_panels.filter(panel => panel.id == expertPanel.id).length > 0
    }

    canEditTopic(topic) {
        if (topic.curator_id == this.user.id) {
            return true
        }
        if (this.hasPermission('manage panel topics') && this.inExpertPanel(topic.expert_panel)) {
            return true;
        }
        
        if (this.hasRole('programmer') || this.hasRole('admin')) {
            return true;
        }
        return false;
    }

}

export default User