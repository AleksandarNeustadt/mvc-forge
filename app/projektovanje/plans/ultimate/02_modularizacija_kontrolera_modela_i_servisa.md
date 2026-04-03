# Ultimate plan 02: Modularizacija kontrolera, modela i servisnog sloja

## Cilj

Razbiti prevelike kontrolere i rucno rasutu poslovnu logiku u jasne module, servise, action klase i repozitorijume, tako da sistem ostane domenski sirok ali postane mnogo laksi za odrzavanje i sirenje.

## Grupa A: Mapa monolita i prioritizacija rezanja

- [x] Izmeriti najvece fajlove po broju linija i podeliti ih u prioritetne refaktor grupe, posebno `DashboardController.php`, `DashboardApiController.php`, `ApiController.php` i najvece core klase.
- [x] Za svaki veliki kontroler napraviti mapu odgovornosti:
  - [x] auth/session tokovi,
  - [x] page/blog CRUD,
  - [x] media/upload,
  - [x] users/roles/permissions,
  - [x] geography/i18n,
  - [x] analytics/audit log,
  - [x] API tokeni i integracije.
- [x] Oznaciti metode koje su cisto HTTP adaptacija i metode koje zapravo nose poslovnu logiku, jer samo prve treba da ostanu u kontroleru.
  - [x] Mapa i klasifikacija upisana u `support/02_kontroler_mapa_odgovornosti.md`.

## Grupa B: Novi sloj servisa i repozitorijuma

- [x] Uvesti aplikacione servise po domenu, npr. `PageService`, `UserService`, `AuthService`, `MediaService`, `NavigationService`, `GeoService`, `AuditService`.
  - [x] Uveden prvi servisni rez: `DashboardUserService` za dashboard user-management pravila.
  - [x] Uveden prvi page-management servisni rez: `DashboardPageService`.
  - [x] Uveden prvi navigation-management servisni rez: `DashboardNavigationService`.
  - [x] Uveden prvi language-management servisni rez: `DashboardLanguageService`.
  - [x] Uveden prvi geography-management servisni rez: `DashboardGeoService`.
  - [x] Uveden prvi blog-post servisni rez: `DashboardBlogPostService`.
  - [x] Uveden prvi blog-taxonomy servisni rez: `DashboardBlogTaxonomyService`.
  - [x] Uveden prvi roles/permissions servisni rez: `DashboardRoleService`.
  - [x] Uveden prvi analytics/messages servisni rez: `DashboardIpTrackingService` i `DashboardContactMessageService`.
  - [x] Uveden prvi schema-management servisni rez: `DashboardSchemaService`.
  - [x] Uveden prvi media/upload servisni rez: `DashboardMediaService`.
  - [x] Uveden prvi API response-formatting servisni rez: `ApiResponseFormatterService`.
  - [x] Uveden prvi dashboard API resource servisni rez: `DashboardApiResourceService`.
  - [x] Uveden prvi dashboard API query/enrichment servisni rez: `DashboardApiQueryService`.
  - [x] `createPage()` / `editPage()` view-model priprema izvucena u `DashboardPageService::buildPageFormData()`.
- [x] Uvesti repozitorijume ili model metode za ponavljajuce DB obrasce, npr. `findById`, `findBySlug`, `paginate`, `existsByEmail`, uz bind parametre bez string konkatenacije korisnickog unosa.
  - [x] Dodati genericki `Model::findByField()` i `Model::existsByField()` helper-i i njima zamenjen deo dupliranih `findBySlug` implementacija.
- [x] Sve operacije koje menjaju vise tabela staviti u transakcije.
  - [x] Dodan `Database::transaction()` helper i primenjen na `User::syncRoles()`, `Role::syncPermissions()`, `BlogPost::syncCategories()`, `BlogPost::syncTags()`, `DashboardBlogPostService::deletePostRelations()` i `DashboardRoleService::detachRoleRelations()`.
  - [x] `DashboardNavigationService::deleteMenu()` atomizuje odvezivanje `pages.navbar_id` i brisanje menija.
  - [x] `DashboardBlogPostService::deletePost()` i `DashboardBlogTaxonomyService::deleteCategory()` / `deleteTag()` atomizuju relation cleanup i brisanje zapisa.
  - [x] `DashboardRoleService::deleteRole()` atomizuje uklanjanje role relacija i brisanje role zapisa.
- [x] Svaki servis mora imati jasnu granicu:
  - [x] ne cita direktno `$_POST` / `$_GET`,
  - [x] prima vec validirane podatke,
  - [x] vraca strukturisan rezultat ili baca domen-validan exception.
  - [x] Provera `core/services/*.php` ne nalazi direktan pristup `$_POST`, `$_GET` ili `$_REQUEST`.
  - [x] `DashboardUserService::validateUniqueness()` prima eksplicitne argumente i ne cita superglobale.
  - [x] `DashboardUserService::applyAvatarChanges()` i `deleteAvatarFile()` izdvajaju avatar poslovnu/file logiku iz kontrolera uz eksplicitne ulaze.
  - [x] `DashboardUserService::buildUserListData()`, `clearFormState()`, `normalizeUserArray()`, `buildEditFormData()`, `applyProfileData()`, `applyStatusTransitions()`, `assignUniqueSlug()` i `syncRolesIfAllowed()` izdvajaju users list/edit/status/role pravila iz kontrolera.
  - [x] `DashboardUserService::validateCreateInput()`, `validateUpdateInput()`, `createUser()`, `updateUser()`, `deleteUser()`, `banUser()`, `unbanUser()` i `approveUser()` izdvajaju user mutation flow, transakcije, audit/log i self-action guard pravila iz kontrolera.
  - [x] `DashboardPageService::normalizeRoute()`, `normalizeOptionalId()` i `validateRouteAndSlugUniqueness()` izdvajaju deo page pravila iz kontrolera.
  - [x] `DashboardPageService::getPageList()` izdvajaja page list query i view-model transformaciju iz kontrolera.
  - [x] `DashboardPageService::buildPageFormData()` sklapa parent pages, blog resurse, navigation menu i language opcije bez citanja superglobala.
  - [x] `DashboardPageService::mapPageTypeForStorage()`, `buildDisplayOptions()`, `applyBlogAssociations()` i `preparePageForEdit()` izdvajaju page-type/display-options pravila iz kontrolera.
  - [x] `DashboardPageService::validatePageInput()`, `savePage()` i `deletePage()` izdvajaju page create/update/delete flow, route-cache invalidaciju i page-form mapiranje iz kontrolera.
  - [x] `DashboardNavigationService::getMenuList()`, `buildLanguageOptions()`, `buildEditFormData()`, `applyMenuData()`, `saveMenu()`, `detachPagesFromMenu()` i `deleteMenu()` izdvajaju navigation menu pravila iz kontrolera.
  - [x] `DashboardLanguageService::getLanguageList()`, `buildGeoFormData()`, `buildEditFormData()`, `codeExists()`, `validateLanguageInput()`, `applyLanguageData()`, `saveLanguage()`, `validateDeletion()`, `deleteLanguage()` i `setDefaultLanguage()` izdvajaju language pravila iz kontrolera.
  - [x] `DashboardGeoService::buildContinentOptions()`, `buildContinentEditData()`, `buildRegionEditData()`, `continentCodeExists()`, `regionCodeExists()`, `validateContinentInput()`, `validateRegionInput()`, `applyContinentData()`, `applyRegionData()`, `saveContinent()`, `saveRegion()`, `validateContinentDeletion()`, `validateRegionDeletion()`, `deleteContinent()` i `deleteRegion()` izdvajaju continent/region pravila iz kontrolera.
  - [x] `DashboardBlogPostService::getPostList()`, `buildCreateFormData()`, `buildEditFormData()`, `buildPreviewData()`, `normalizeSlug()`, `postSlugExists()`, `validatePostInput()`, `applyPostData()`, `syncPostCategories()`, `savePost()`, `deletePostRelations()` i `deletePost()` izdvajaju blog post pravila iz kontrolera.
  - [x] `DashboardBlogTaxonomyService::getCategoryList()`, `buildCategoryFormData()`, `buildCategoryEditFormData()`, `buildCategoryPreviewData()`, `normalizeSlug()`, `categorySlugExists()`, `validateCategoryInput()`, `validateCategoryParent()`, `applyCategoryData()`, `saveCategory()`, `validateCategoryDeletion()`, `deleteCategoryRelations()` i `deleteCategory()` izdvajaju blog category pravila iz kontrolera.
  - [x] `DashboardBlogTaxonomyService::getTagList()`, `buildTagFormData()`, `buildTagEditFormData()`, `tagSlugExists()`, `validateTagInput()`, `applyTagData()`, `saveTag()`, `deleteTagRelations()` i `deleteTag()` izdvajaju blog tag pravila iz kontrolera.
  - [x] `DashboardRoleService::getRoleList()`, `getPermissionGroups()`, `buildEditFormData()`, `normalizeRoleData()`, `slugExists()`, `validateRoleData()`, `validateSystemRoleMutation()`, `syncPermissions()`, `detachRoleRelations()`, `createRole()`, `updateRole()` i `deleteRole()` izdvajaju role/permission pravila iz kontrolera.
  - [x] `DashboardIpTrackingService::buildDashboardData()` i `DashboardContactMessageService::buildMessageListData()`, `buildShowData()`, `markAsRead()`, `markAsReplied()` i `deleteMessage()` izdvajaju analytics/messages pravila iz kontrolera.
  - [x] `DashboardSchemaService::getDatabaseOverview()`, `getTableInfo()`, `tableExists()`, `createTable()`, `normalizeColumnDefinition()`, `addColumn()`, `dropTable()`, `dropColumn()` i `buildColumnType()` izdvajaju schema-manager pravila iz kontrolera.
  - [x] `DashboardMediaService::uploadTinyMceImage()`, `getServedBlogImage()` i `uploadFeaturedImage()` izdvajaju blog media upload/serve pravila iz kontrolera.
  - [x] `ApiResponseFormatterService::formatPage()`, `formatMenu()`, `formatPost()`, `formatCategory()`, `formatTag()` i `formatLanguage()` izdvajaju API response transformaciju iz `ApiController`.
  - [x] `DashboardApiResourceService::getModelClass()`, `getValidationRules()`, `prepareData()`, `actionAllowed()` i `handleCustomAction()` izdvajaju generic dashboard API resource/action pravila iz `DashboardApiController`.
  - [x] `DashboardApiQueryService::applyDynamicFilters()`, `applySearchToQuery()` i `enrichResourceData()` izdvajaju query/filter/search/enrichment pravila iz `DashboardApiController`.
  - [x] `DashboardApiQueryService::getBlogCategoriesWithRelations()`, `getBlogTagsWithRelations()`, `getBlogCategoriesCount()`, `getBlogTagsCount()`, `getBlogPostsWithRelations()`, `getBlogPostsCount()` i `getLanguagesWithRelations()` izdvajaju raw-SQL list/count logiku iz `DashboardApiController`.
  - [x] `DashboardApiQueryService::collectDynamicFilters()` i `getFilterOptions()` izdvajaju query-param filter parsing i filter-options list logiku iz `DashboardApiController`.

## Grupa C: Dekonstrukcija najvecih kontrolera

- [x] Razbiti dashboard/admin kontrolere po resursima ili modulima, npr. `DashboardPageController`, `DashboardUserController`, `DashboardMediaController`, `DashboardNavigationController`, `DashboardSettingsController`.
  - [x] Dashboard web rute prevezane na `DashboardHomeController`, `DashboardSchemaController`, `DashboardUserController`, `DashboardRoleController`, `DashboardPageController`, `DashboardNavigationController`, `DashboardLanguageController`, `DashboardGeoController`, `DashboardBlogController` i `DashboardContactMessageController`.
- [x] Razbiti javni API kontroler po API domenima ili koristiti manje action klase ako ruta-grupe to lakse podrzavaju.
  - [x] Public API rute prevezane na `ApiAuthController`, `ApiPageController`, `ApiMenuController`, `ApiPostController`, `ApiCategoryController`, `ApiTagController` i `ApiLanguageController`.
  - [x] Prvi rez: response-formatting helper-i iz `ApiController` prebaceni u `ApiResponseFormatterService`.
  - [x] Prvi rez za `DashboardApiController`: app metadata, validaciona pravila, payload priprema i custom action dispatch prebaceni u `DashboardApiResourceService`.
  - [x] Drugi rez za `DashboardApiController`: generic query filter/search i relation enrichment helper-i prebaceni u `DashboardApiQueryService`.
  - [x] Treci rez za `DashboardApiController`: blog category/tag/post i language listing/count query metode prebacene u `DashboardApiQueryService`, a kontroler sveden na tanke delegate metode.
  - [x] Cetvrti rez za `DashboardApiController`: filter-options endpoint delegiran u `DashboardApiQueryService`, uklonjeno direktno `$_GET` citanje iz kontrolera i obrisani mrtvi `applySearch()` / `applySorting()` helper-i.
- [x] U svakom refaktoru zadrzati stabilne rute i response formate dok god nema namerne breaking promene.
- [x] Iz kontrolera izbaciti direktni SQL, dupliranu validaciju i proceduralne blokove duze od razumno citljivog opsega.
  - [x] Iz `DashboardController` izdvojeni user uniqueness i avatar management blokovi u `DashboardUserService`.
  - [x] Iz `DashboardController` izvucena users list/show/edit view-model priprema, profil mapiranje, status tranzicije, slug generisanje i role sync.
  - [x] Iz `DashboardController` izdvojeni route/slug uniqueness i ID normalizacija za pages u `DashboardPageService`.
  - [x] Iz `DashboardController` uklonjena duplicirana priprema page create/edit form opcija.
  - [x] Iz `DashboardController` izvuceno mapiranje page type-a, display-options build i edit-form transformacija stranice.
  - [x] Iz `DashboardController` izvucena navigation menu transformacija, language opcije i menu detach/update pravila.
  - [x] Iz `DashboardController` izvucena language list agregacija, continent/region form-data, code uniqueness i delete guards logika.
  - [x] Iz `DashboardController` izvucena continent/region forma, mapiranje inputa, code uniqueness i delete guard logika.
  - [x] Iz `DashboardController` izvucena blog post list/form priprema, slug provere, mapiranje post polja, sync kategorija i relation cleanup.
  - [x] Iz `DashboardController` izvucena blog category/tag list priprema, form opcije, slug unique provere, parent validacija i mapiranje inputa.
  - [x] Iz `DashboardController` izvucena roles/permissions list priprema, slug normalizacija, system-role guard, permission sync i relation cleanup.
  - [x] Iz `DashboardController` izvucena IP tracking dashboard agregacija i contact-message list/statuse/paginacija.
  - [x] Iz `DashboardController` izvucena database/table/column schema-management logika.
  - [x] Iz `DashboardController` izvucena blog image upload/serve i featured-image upload logika.
- [x] Za svaki izdvojeni modul dodati kratku internu dokumentaciju "sta je odgovornost ovog kontrolera/servisa".

## Grupa D: Model i schema konvencije

- [x] Dogovoriti zajednicke konvencije za modele: naziv tabele, primarni kljuc, fillable/safe polja, povratni format i osnovni CRUD obrazac.
  - [x] Dodan radni standard u `support/02_model_schema_i_raw_sql_konvencije.md` za `$table`, `$primaryKey`, `$fillable`, `$hidden`, `$casts`, finder obrasce, transakcije i povratne tipove.
- [x] Odabrati jedan kanonski nacin upravljanja semom baze: ili postojece PHP migracije/builder kao primarni kanal, ili jasno razdvojiti "legacy SQL skripte" od "aktivnog deploy puta".
  - [x] Dokumentovano da je runtime/admin schema put `DatabaseBuilder` + `DatabaseTableBuilder` + `DashboardSchemaService`, a legacy migracije ostaju izolovan deploy debt do plana 05.
- [x] Dodati osnovni PHPDoc za modele i servise gde API nije samorazumljiv.
  - [x] Dodata support dokumentacija za kontroler mapu, model/schema/raw-SQL standard i view-model/template dug.
- [x] Ukloniti ili izolovati mrtve/duplirane migracione i setup skripte tek kada postoji novi install/deploy tok iz plana 05.
  - [x] Za sada eksplicitno odlozeno u plan 05 i dokumentovano da su legacy SQL/migracije deploy debt dok se ne zavrsi install paket.

## Grupa E: View sloj i kontrolisana prezentaciona logika

- [x] Proveriti da li su u view-ovima ostali komadi poslovne logike koji treba da predju u controller/service.
  - [x] Popisani `User::find`, `Database::select`, `BlogPost::find`, `Language::findByCode` i permission/business branch primeri koji jos zive u template-ima u `support/02_view_model_i_template_dug.md`.
- [x] Standardizovati view model podatke tako da template dobija jasne, pripremljene strukture, a ne proizvoljne unutrasnje objekte.
  - [x] Definisan standard da kontroler/servis priprema jedan view-model oblik, a template ne normalizuje "objekat ili niz"; postojece izuzetke smo evidentirali kao sledeci cleanup target.
- [x] Ako postoje admin/public template varijante sa dupliranjem, evidentirati gde je korisno uvesti komponente ili partials.
  - [x] Evidentirani kandidati za partial/component ekstrakciju u `support/02_view_model_i_template_dug.md`.

## Kriterijumi zavrsetka

- [x] Najveci kontroleri vise nisu "God object" klase, nego su podeljeni u manje HTTP adaptere i servise.
  - [x] Route entrypoint-i su podeljeni na manje dashboard/API adapter klase, a poslovna logika glavnih domena je izvucena u servise; legacy parent kontroleri ostaju kao shared implementacija i mogu se dalje fizicki tanjiti bez breaking promene.
- [x] Novi DB kod ne uvodi sirovu konkatenaciju korisnickog inputa u SQL.
  - [x] U `Database` uvedeni `assertIdentifier()` i `quoteIdentifier()` guard/helper-i za bezbedno koriscenje imena tabela/kolona.
  - [x] `DatabaseBuilder`, `DatabaseTableBuilder` i `DashboardSchemaService` koriste validirane/quote-ovane identifikatore i quote-ovane default vrednosti pri schema operacijama.
  - [x] `QueryBuilder` sada validira/quote-uje table, select, where, join, group by, having, order by i insert/update/delete kolone, uz whitelist za SQL operatore, sort smer i join tip.
  - [x] Standardni API search flow vise ne koristi `whereRaw()`, vec `QueryBuilder::whereAnyLike()` sa quote-ovanim kolonama i bind parametrima.
  - [x] Popisan i dokumentovan raw-SQL escape hatch standard u `support/02_model_schema_i_raw_sql_konvencije.md`, ukljucujuci dozvoljene i nedozvoljene slucajeve.
  - [x] Preostali legacy raw SQL pozivi su popisani i ostaju dozvoljeni samo pod dokumentovanim pravilima; dalje prebacivanje na typed helper-e ide inkrementalno kroz sledece planove bez menjanja ovog standarda.
- [x] Servisni sloj postoji za glavne domene i olaksava testiranje bez kompletnog HTTP stacka.
- [x] Dodavanje nove funkcionalnosti ima jasan odgovor: "u koji modul, servis, kontroler i model ide".
  - [x] Kanonski odgovor dokumentovan u `support/02_model_schema_i_raw_sql_konvencije.md` i `support/02_kontroler_mapa_odgovornosti.md`.
