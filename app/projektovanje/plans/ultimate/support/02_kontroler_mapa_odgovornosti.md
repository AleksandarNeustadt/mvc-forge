# Plan 02 support: mapa odgovornosti velikih kontrolera

## Route adapter split

Dashboard web rute su sada razdvojene po ulaznim adapter klasama:

- `DashboardHomeController`: `/dashboard`, `/dashboard/ip-tracking`
- `DashboardSchemaController`: `/dashboard/database*`
- `DashboardUserController`: `/dashboard/users*`
- `DashboardRoleController`: `/dashboard/users/roles*`, `/dashboard/users/permissions`
- `DashboardPageController`: `/dashboard/pages*`
- `DashboardNavigationController`: `/dashboard/navigation-menus*`
- `DashboardLanguageController`: `/dashboard/languages*`
- `DashboardGeoController`: `/dashboard/continents*`, `/dashboard/regions*`
- `DashboardBlogController`: `/dashboard/blog*`, `/storage/uploads/blog/{filename}`
- `DashboardContactMessageController`: `/dashboard/contact-messages*`

Public API rute su razdvojene po ulaznim adapter klasama:

- `ApiAuthController`: `/api/auth/*`
- `ApiPageController`: `/api/pages*`
- `ApiMenuController`: `/api/menus*`
- `ApiPostController`: `/api/posts*`
- `ApiCategoryController`: `/api/categories*`
- `ApiTagController`: `/api/tags*`
- `ApiLanguageController`: `/api/languages*`

Napomena: ove adapter klase trenutno dele postojece parent implementacije (`DashboardController`, `ApiController`) da bi URL-ovi i response formati ostali stabilni. Sledeci dublji rez moze fizicki seliti metode iz parent klasa bez promene route mape.

## DashboardController.php

### Auth/session tokovi
- `createUser`, `storeUser`, `editUser`, `updateUser`, `deleteUser`, `banUser`, `unbanUser`, `approveUser`
- HTTP adapteri su public action metode; poslovna pravila su već prebačena u `DashboardUserService`.

### Page/blog CRUD
- Pages: `pages`, `createPage`, `storePage`, `editPage`, `updatePage`, `deletePage`
- Blog posts: `blogPosts`, `createBlogPost`, `storeBlogPost`, `previewBlogPost`, `editBlogPost`, `updateBlogPost`, `deleteBlogPost`
- Blog taxonomy: `blogCategories`, `createBlogCategory`, `storeBlogCategory`, `previewBlogCategory`, `editBlogCategory`, `updateBlogCategory`, `deleteBlogCategory`, `blogTags`, `createBlogTag`, `storeBlogTag`, `editBlogTag`, `updateBlogTag`, `deleteBlogTag`
- Business logika je već uglavnom u `DashboardPageService`, `DashboardBlogPostService` i `DashboardBlogTaxonomyService`; preview metode su još mešavina HTTP i view-model sklapanja.

### Media/upload
- `uploadBlogImage`, `serveBlogImage`, `uploadFeaturedImage`
- Validacija fajla i fajl-serving su u `DashboardMediaService`; kontroler drži CSRF i HTTP response adapter.

### Users/roles/permissions
- Users: `users`, `showUser`, `createUser`, `storeUser`, `editUser`, `updateUser`, `deleteUser`, `banUser`, `unbanUser`, `approveUser`
- Roles/permissions: `roles`, `createRole`, `storeRole`, `editRole`, `updateRole`, `deleteRole`, `permissions`
- Business logika je u `DashboardUserService` i `DashboardRoleService`; kontroler i dalje nosi deo transakcionog orkestriranja za user create/update.

### Geography/i18n
- Navigation/languages/world: `navigationMenus`, `createNavigationMenu`, `storeNavigationMenu`, `editNavigationMenu`, `updateNavigationMenu`, `deleteNavigationMenu`, `languages`, `createLanguage`, `storeLanguage`, `editLanguage`, `updateLanguage`, `deleteLanguage`, `setDefaultLanguage`, `continents`, `createContinent`, `storeContinent`, `editContinent`, `updateContinent`, `deleteContinent`, `regions`, `createRegion`, `storeRegion`, `editRegion`, `updateRegion`, `deleteRegion`
- Business logika je u `DashboardNavigationService`, `DashboardLanguageService` i `DashboardGeoService`.

### Analytics/audit log
- `ipTracking`, `contactMessages`, `showContactMessage`, `markContactMessageRead`, `markContactMessageReplied`, `deleteContactMessage`
- Agregacija i status-promene su u `DashboardIpTrackingService` i `DashboardContactMessageService`.
- Audit logging se još direktno poziva iz `storeUser`, `updateUser`, `deleteUser`.

### API tokeni i integracije
- Nema dedicated API token CRUD akcija u `DashboardController`; ova oblast je dominantno u `DashboardApiController` i `ApiController`.

### Schema management
- `database`, `showTable`, `createTable`, `storeTable`, `createColumn`, `storeColumn`, `dropTable`, `dropColumn`
- Business logika je u `DashboardSchemaService`.

### Preostale business-helper metode u kontroleru
- `validateUserUniqueness`, `handleAvatarUpload`, `deleteAvatarFile` su tanki wrapper-i ka `DashboardUserService` i služe za backward kompatibilnost tokom refaktora.

## DashboardApiController.php

### API resource CRUD
- Public HTTP adapteri: `index`, `show`, `create`, `update`, `delete`, `getFilterOptions`, `action`
- Business/helper metode: `applyDynamicFilters`, `applySearchToQuery`, `enrichResourceData`, `getBlogCategoriesWithRelations`, `getBlogTagsWithRelations`, `getBlogCategoriesCount`, `getBlogTagsCount`, `getBlogPostsWithRelations`, `getBlogPostsCount`, `getLanguagesWithRelations`, `getModelClass`, `getValidationRules`, `prepareData`, `applySearch`, `applySorting`, `handleCustomAction`, `handleUserAction`
- Ovo je sledeći kandidat za servisni rez po API domenu jer helper metode već jasno otkrivaju module.

## ApiController.php

### Auth/session tokovi
- `login`, `logout`, `me`

### Page/blog CRUD
- Pages: `listPages`, `getPage`, `createPage`, `updatePage`, `deletePage`
- Menus: `listMenus`, `getMenu`, `createMenu`, `updateMenu`, `deleteMenu`
- Posts: `listPosts`, `getPost`, `createPost`, `updatePost`, `bulkCreatePosts`, `deletePost`
- Categories/tags: `listCategories`, `getCategory`, `createCategory`, `updateCategory`, `deleteCategory`, `listTags`, `getTag`, `createTag`, `updateTag`, `deleteTag`
- Languages: `listLanguages`, `getLanguage`, `getLanguageByCode`, `createLanguage`, `updateLanguage`, `deleteLanguage`

### Business/helper metode
- `formatPage`, `formatMenu`, `formatPost`, `formatCategory`, `formatTag`, `formatLanguage`, `jsonResponse`
- CRUD metode su većinom HTTP adapteri sa inline validacijom i formatiranjem; `format*` metode su čista prezentaciona transformacija i dobar kandidat za response formatter servis.
