class User {
    constructor(userData) {
        this.user = userData
    }

    getRoles() {
        return this.user.roles.map(role => role.name)
    }

    hasRole(roleName) {
        return this.user.roles.filter((role) => role.name == roleName).length > 0
    }

    hasAnyRole(...roleNames) {
        const roles = this.getRoles()
        return roleNames.filter(roleName => roles.includes(roleName)).length > 0
    }

    hasPermission(permName) {
        return this.user.permissions.filter(permission => permission.name == permName).length > 0
    }

    inExpertPanel(expertPanel) {
        if (!expertPanel) {
            return false;
        }
        return this.user.expert_panels.filter(panel => panel.id == expertPanel.id).length > 0
    }

    isCurator() {
        const curatorPanels = this.user.expert_panels
            .filter(panel => panel.pivot.is_curator == 1);

        return curatorPanels.length > 0
    }

    isPanelCoordinator(expertPanel) {
        return this.user.expert_panels.filter(function(panel) {
            return panel.id == expertPanel.id &&
                panel.pivot.is_coordinator === 1;
        }).length > 0;
    }

    isCurationCurator(curation) {
        if (!curation) {
            return false;
        }
        return this.user.id == curation.curator_id
    }

    canSelectExpertPanel(expertPanel) {
        return this.inExpertPanel(expertPanel) || this.hasRole('programmer') || this.hasRole('admin')
    }


    canEditPanelCurations(expertPanel) {
        if (this.hasRole('programmer') || this.hasRole('admin')) {
            return true;
        }
        if (!this.inExpertPanel(expertPanel)) {
            return false;
        }
        return this.user.expert_panels.filter(function(panel) {
            return panel.id == expertPanel.id &&
                (panel.pivot.can_edit_curations === 1 ||
                    panel.pivot.is_coordinator === 1);
        }).length > 0;
    }

    canSelectCurationStatus(status, curation) {
        return this.canEditCuration(curation)
    }

    canEditCuration(curation) {
        if (curation && curation.curator_id == this.user.id) {
            return true
        }
        if (this.hasRole('programmer') || this.hasRole('admin')) {
            return true;
        }

        if (this.canEditPanelCurations(curation.expert_panel)) {
            return true;
        }

        return false;
    }

    canAddCurations() {
        if (this.hasRole('programmer') || this.hasRole('admin')) {
            return true;
        }
        return this.user.expert_panels.filter(function(panel) {
            return panel.pivot.is_coordinator
        }).length > 0;
    }

    canUpdateCurations() {
        return this.canAddCurations();
    }

    canDeleteCuration(curation) {
        if (this.hasRole('programmer') || this.hasRole('admin')) {
            return true;
        }

        if (this.hasPermission('delete curations') && this.canEditCuration(curation)) {
            return true
        }

        if (this.isPanelCoordinator(curation.expert_panel)) {
            return true
        }

        return false;
    }

}

export default User