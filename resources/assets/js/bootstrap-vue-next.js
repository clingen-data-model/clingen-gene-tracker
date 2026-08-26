import {
    BBadge,
    BButton,
    BCard,
    BCollapse,
    BDropdownDivider,
    BDropdownItem,
    BForm,
    BFormGroup,
    BFormInput,
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
    vBToggle,
} from 'bootstrap-vue-next'

const components = {
    BBadge,
    BButton,
    BCard,
    BCollapse,
    BDropdownDivider,
    BDropdownItem,
    BForm,
    BFormGroup,
    BFormInput,
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
}

export default function registerBootstrapVueNext(app) {
    Object.entries(components).forEach(([name, component]) => {
        app.component(name, component)
    })

    app.directive('b-toggle', vBToggle)
}
