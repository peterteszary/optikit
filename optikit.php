<?php
/**
 * Plugin Name:       OptiKit
 * Plugin URI:        https://example.com/optikit
 * Description:       Egy eszköztár, amely optimalizációs és adminisztrációs modulokkal bővíti a WordPress-t.
 * Version:           1.0.1
 * Author:            Teszáry Péter
 * Author URI:        https://peterteszary.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       optikit
 */

// Megakadályozzuk a fájl közvetlen elérését
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fő OptiKit osztály.
 * Singleton mintát használ, hogy csak egyszer példányosodjon.
 */
final class OptiKit_Main {

    private static $_instance = null;

    /**
     * Singleton példány létrehozása és visszaadása.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Konstruktor. Itt akasztjuk be a fő funkciókat.
     */
    private function __construct() {
        add_action( 'admin_menu', [ $this, 'create_admin_menu' ] );
        $this->load_modules();
    }

    /**
     * Létrehozza a fő "OptiKit" menüpontot az admin felületen.
     * A modulok ehhez a fő menüponthoz fognak saját almenüket hozzáadni.
     */
    public function create_admin_menu() {
        add_menu_page(
            'OptiKit Beállítások',
            'OptiKit',
            'manage_options',
            'optikit', // Ez lesz a szülő menü "slug"-ja
            [ $this, 'main_page_html' ],
            'dashicons-admin-tools',
            100
        );
    }

    /**
     * A fő OptiKit oldal tartalmát jeleníti meg.
     * Ez egyfajta "irányítópultként" funkcionál.
     */
    public function main_page_html() {
        ?>
        <div class="wrap">
            <h1>Üdvözlünk az OptiKit vezérlőpultján!</h1>
            <p>A bal oldali menüben válaszd ki a beállítani kívánt modult.</p>
            <p>Jelenleg aktív modulok:</p>
            <ul>
                <li>Asset Manager (Erőforrás Kezelő)</li>
            </ul>
        </div>
        <?php
    }

    /**
     * Betölti az összes modult a /modules/ könyvtárból.
     * Minden modulnak a saját mappájában kell lennie, és egy azonos nevű .php fájlt kell tartalmaznia.
     */
    public function load_modules() {
        $modules_path = plugin_dir_path( __FILE__ ) . 'modules';
        
        if ( ! is_dir( $modules_path ) ) {
            return;
        }

        foreach ( scandir( $modules_path ) as $module ) {
            if ( $module === '.' || $module === '..' ) {
                continue;
            }
            $module_file = $modules_path . '/' . $module . '/' . $module . '.php';
            if ( is_readable( $module_file ) ) {
                require_once $module_file;
            }
        }
    }
}

// Indítjuk a bővítményt!
OptiKit_Main::instance();