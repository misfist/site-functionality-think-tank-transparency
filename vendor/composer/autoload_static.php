<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit5aaf75406038386c7036960a9cdb1020
{
    public static $files = array (
        'e2bf25bea31b2773509eb6c14a13ecac' => __DIR__ . '/../..' . '/src/functions.php',
        'e1fd76e2b159e9584a1c441b487c9a12' => __DIR__ . '/../..' . '/src/helpers.php',
        'ed9d40526b5744f58f5598bf60a5db2a' => __DIR__ . '/../..' . '/src/app/integrations/wp-import/functions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Site_Functionality\\' => 19,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Site_Functionality\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'S' => 
        array (
            'Site_Functionality\\' => 
            array (
                0 => __DIR__ . '/../..' . '/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Site_Functionality\\App\\Admin\\Admin_Assets' => __DIR__ . '/../..' . '/src/app/admin/class-admin-assets.php',
        'Site_Functionality\\App\\Admin\\Admin_Settings' => __DIR__ . '/../..' . '/src/app/admin/class-admin-settings.php',
        'Site_Functionality\\App\\Admin\\Editor' => __DIR__ . '/../..' . '/src/app/admin/class-editor.php',
        'Site_Functionality\\App\\Custom_Fields\\Custom_Fields' => __DIR__ . '/../..' . '/src/app/custom-fields/class-custom-fields.php',
        'Site_Functionality\\App\\Frontend\\Frontend_Assets' => __DIR__ . '/../..' . '/src/app/frontend/class-frontend-assets.php',
        'Site_Functionality\\App\\Post_Types\\Donor' => __DIR__ . '/../..' . '/src/app/post-types/class-donor.php',
        'Site_Functionality\\App\\Post_Types\\Post_Types' => __DIR__ . '/../..' . '/src/app/post-types/class-post-types.php',
        'Site_Functionality\\App\\Post_Types\\Think_Tank' => __DIR__ . '/../..' . '/src/app/post-types/class-think-tank.php',
        'Site_Functionality\\App\\Post_Types\\Transaction' => __DIR__ . '/../..' . '/src/app/post-types/class-transaction.php',
        'Site_Functionality\\App\\Taxonomies\\Donor' => __DIR__ . '/../..' . '/src/app/taxonomies/class-donor.php',
        'Site_Functionality\\App\\Taxonomies\\Donor_Type' => __DIR__ . '/../..' . '/src/app/taxonomies/class-donor-type.php',
        'Site_Functionality\\App\\Taxonomies\\Taxonomies' => __DIR__ . '/../..' . '/src/app/taxonomies/class-taxonomies.php',
        'Site_Functionality\\App\\Taxonomies\\Think_Tank' => __DIR__ . '/../..' . '/src/app/taxonomies/class-think-tank.php',
        'Site_Functionality\\App\\Taxonomies\\Year' => __DIR__ . '/../..' . '/src/app/taxonomies/class-year.php',
        'Site_Functionality\\Common\\Abstracts\\Base' => __DIR__ . '/../..' . '/src/common/abstracts/abstract-base.php',
        'Site_Functionality\\Common\\Abstracts\\Post_Type' => __DIR__ . '/../..' . '/src/common/abstracts/abstract-post-type.php',
        'Site_Functionality\\Common\\Abstracts\\Taxonomy' => __DIR__ . '/../..' . '/src/common/abstracts/abstract-taxonomy.php',
        'Site_Functionality\\Common\\WP_Includes\\Activator' => __DIR__ . '/../..' . '/src/common/wp-includes/class-activator.php',
        'Site_Functionality\\Common\\WP_Includes\\Deactivator' => __DIR__ . '/../..' . '/src/common/wp-includes/class-deactivator.php',
        'Site_Functionality\\Common\\WP_Includes\\I18n' => __DIR__ . '/../..' . '/src/common/wp-includes/class-i18n.php',
        'Site_Functionality\\Integrations\\API\\API' => __DIR__ . '/../..' . '/src/app/integrations/api/class-api.php',
        'Site_Functionality\\Integrations\\CLI\\Commands' => __DIR__ . '/../..' . '/src/app/integrations/cli/class-commands.php',
        'Site_Functionality\\Integrations\\Integrations' => __DIR__ . '/../..' . '/src/app/integrations/class-integrations.php',
        'Site_Functionality\\Integrations\\WP_Import\\Actions' => __DIR__ . '/../..' . '/src/app/integrations/wp-import/class-actions.php',
        'Site_Functionality\\Settings' => __DIR__ . '/../..' . '/src/class-settings.php',
        'Site_Functionality\\Site_Functionality' => __DIR__ . '/../..' . '/src/class-site-functionality.php',
        'Site_Functionality\\Transaction_Data_CLI' => __DIR__ . '/../..' . '/src/app/integrations/wp-import/functions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit5aaf75406038386c7036960a9cdb1020::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit5aaf75406038386c7036960a9cdb1020::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit5aaf75406038386c7036960a9cdb1020::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit5aaf75406038386c7036960a9cdb1020::$classMap;

        }, null, ClassLoader::class);
    }
}
