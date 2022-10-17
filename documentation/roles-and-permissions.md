# Roles & Permissions
System roles and permissions in the GT are handled by [Spatie's laravel-permissions](https://github.com/spatie/laravel-permission/) package.

Roles include: 
* programmer - have all permissions and full access to system features.
* admin - have most permissions, but cannot execute some actions to prevent data corruption.  Admins also have access to the admin dashboard.
* viewer - can only view curations.

Generally permissions are grouped around entities in the system.  Most entities have individual permissions for `list`, `create`, `update`, and `delete` the entity.  Entities with this permissions include:
* users - also has a `deactivate` permission
* expert-panels - also has a `deactivate` permission
* curation-statuses
* working-groups
* curation-types
* rationales
* pages
* curations
* mois

Additionally, there are permissions not related to entities:
* `manage panel curations` - ?
* `update curation gdm_uuid` - Used to determine if a user can update a curation's gdm_uuid (gci id for a curation).

