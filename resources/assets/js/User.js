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

    isPanelCoordinator(expertPanel) {
        return this.user.expert_panels.filter(function (panel) {
            return panel.id == expertPanel.id 
                    && panel.pivot.is_coordinator === 1;
        }).length > 0;
    }

    isTopicCurator(topic) {
        return this.user.id == topic.curator_id
    }

    canSelectExpertPanel(expertPanel) {
        return this.inExpertPanel(expertPanel) || this.hasRole('programmer') || this.hasRole('admin')
    }

    
    canEditPanelTopics(expertPanel) {
        if (!this.inExpertPanel(expertPanel)) {
            return false;
        }
        return this.user.expert_panels.filter(function (panel) {
            return panel.id == expertPanel.id 
                && (panel.pivot.can_edit_topics === 1 
                    || panel.pivot.is_coordinator === 1);
        }).length > 0;
    }

    canSelectTopicStatus(status, topic) {
        switch (status.name) {
            case 'Recuration assigned':
                return this.isPanelCoordinator(topic.expert_panel);            
                break;
        
            default:
                return this.canEditTopic(topic)
        }
    }

    canEditTopic(topic) {
        if (topic.curator_id == this.user.id) {
            return true
        }
        if (this.hasRole('programmer') || this.hasRole('admin')) {
            return true;
        }

        if (this.canEditPanelTopics(topic.expert_panel)) {
            return true;
        }
        
        return false;
    }

    canAddTopics() {
        if (this.hasRole('programmer') || this.hasRole('admin')) {
            return true;
        }
        return this.user.expert_panels.filter(function (panel) {
            return panel.pivot.is_coordinator
        }).length > 0;
    }

}

export default User