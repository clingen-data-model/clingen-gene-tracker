import {
    BAlert,
    BBadge,
    BButton,
    BCard,
    BCollapse,
    BDropdownDivider,
    BDropdownItem,
    BForm,
    BFormInvalidFeedback,
    BFormGroup,
    BFormInput,
    BFormTextarea,
    BFormRadioGroup,
    BFormSelect,
    BModal,
    BNavbar,
    BNavbarBrand,
    BNavbarNav,
    BNavbarToggle,
    BNavItem,
    BNavItemDropdown,
    BPagination,
    BPopover,
    BTab,
    BTable,
    BTabs,
    BSpinner,
    vBToggle,
} from 'bootstrap-vue-next'

const components = {
    BAlert,
    BBadge,
    BButton,
    BCard,
    BCollapse,
    BDropdownDivider,
    BDropdownItem,
    BForm,
    BFormInvalidFeedback,
    BFormGroup,
    BFormInput,
    BFormTextarea,
    BFormRadioGroup,
    BFormSelect,
    BModal,
    BNavbar,
    BNavbarBrand,
    BNavbarNav,
    BNavbarToggle,
    BNavItem,
    BNavItemDropdown,
    BPagination,
    BPopover,
    BTab,
    BTable,
    BTabs,
    BSpinner,
}

export default function registerBootstrapVueNext(app) {
    Object.entries(components).forEach(([name, component]) => {
        app.component(name, component)
    })

    app.directive('b-toggle', vBToggle)
}
