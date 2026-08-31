import { describe, expect, it } from 'vitest'
import User from '../../../resources/assets/js/User'

function panel(id, pivot = {}) {
    return {
        id,
        pivot: {
            is_curator: 0,
            is_coordinator: 0,
            can_edit_curations: 0,
            ...pivot,
        },
    }
}

function user({ id = 1, roles = [], permissions = [], panels = [] } = {}) {
    return new User({
        id,
        roles: roles.map(name => ({ name })),
        permissions: permissions.map(name => ({ name })),
        expert_panels: panels,
    })
}

function curation({ curatorId = 99, expertPanel = panel(10), archived = false } = {}) {
    return {
        curator_id: curatorId,
        expert_panel: expertPanel,
        is_archived: archived,
    }
}

describe('User curation permissions', () => {
    it.each(['admin', 'programmer'])('%s can manage, edit, and delete archived curations', role => {
        const currentUser = user({ roles: [role] })
        const archivedCuration = curation({ archived: true })

        expect(currentUser.canManageArchive()).toBe(true)
        expect(currentUser.canEditCuration(archivedCuration)).toBe(true)
        expect(currentUser.canDeleteCuration(archivedCuration)).toBe(true)
        expect(currentUser.canAddCurations()).toBe(true)
    })

    it('allows the assigned curator to edit an active curation', () => {
        const currentUser = user({ id: 7 })

        expect(currentUser.isCurationCurator(curation({ curatorId: 7 }))).toBe(true)
        expect(currentUser.canEditCuration(curation({ curatorId: 7 }))).toBe(true)
    })

    it('allows an expert-panel coordinator to edit and delete panel curations', () => {
        const assignedPanel = panel(12, { is_coordinator: 1 })
        const currentUser = user({ panels: [assignedPanel] })
        const panelCuration = curation({ expertPanel: { id: 12 } })

        expect(currentUser.canEditPanelCurations(panelCuration.expert_panel)).toBe(true)
        expect(currentUser.canEditCuration(panelCuration)).toBe(true)
        expect(currentUser.canDeleteCuration(panelCuration)).toBe(true)
        expect(currentUser.canAddCurations()).toBe(true)
    })

    it('allows a panel member with can_edit_curations to edit panel curations', () => {
        const assignedPanel = panel(12, { can_edit_curations: 1 })
        const currentUser = user({ panels: [assignedPanel] })
        const panelCuration = curation({ expertPanel: { id: 12 } })

        expect(currentUser.canEditPanelCurations(panelCuration.expert_panel)).toBe(true)
        expect(currentUser.canEditCuration(panelCuration)).toBe(true)
        expect(currentUser.canDeleteCuration(panelCuration)).toBe(false)
    })

    it('allows an editable user with delete permission to delete an active curation', () => {
        const currentUser = user({
            id: 7,
            permissions: ['delete curations'],
        })
        const ownedCuration = curation({ curatorId: 7 })

        expect(currentUser.canDeleteCuration(ownedCuration)).toBe(true)
    })

    it('denies unrelated users access to active panel curations', () => {
        const currentUser = user({ panels: [panel(11)] })
        const unrelatedCuration = curation({ expertPanel: { id: 12 } })

        expect(currentUser.inExpertPanel(unrelatedCuration.expert_panel)).toBe(false)
        expect(currentUser.canEditCuration(unrelatedCuration)).toBe(false)
        expect(currentUser.canDeleteCuration(unrelatedCuration)).toBe(false)
    })

    it('restricts archived curations even for their curator and panel coordinator', () => {
        const assignedPanel = panel(12, { is_coordinator: 1, can_edit_curations: 1 })
        const currentUser = user({ id: 7, panels: [assignedPanel] })
        const archivedCuration = curation({
            curatorId: 7,
            expertPanel: { id: 12 },
            archived: true,
        })

        expect(currentUser.canEditCuration(archivedCuration)).toBe(false)
        expect(currentUser.canDeleteCuration(archivedCuration)).toBe(false)
    })
})
