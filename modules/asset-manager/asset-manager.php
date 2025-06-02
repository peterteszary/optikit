<?php
/**
 * Modul: Asset Manager (Erőforrás Kezelő)
 */

// Megakadályozzuk a fájl közvetlen elérését
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Kezeli az Asset Manager modul funkcióit.
 */
class OptiKit_Asset_Manager {

    public function __construct() {
        add_action( 'admin_init', [ $this, 'settings_init' ] );
        add_action( 'admin_menu', [ $this, 'add_submenu_page' ] );
        // CSS hozzáadása a Toggle kapcsolókhoz
        add_action( 'admin_head', [ $this, 'inject_admin_css' ] );
        // A letiltó logika
        add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_selected_assets' ], 100 );
    }

    /**
     * Hozzáadja a modul beállítási oldalát az OptiKit főmenüjéhez.
     */
    public function add_submenu_page() {
        add_submenu_page(
            'optikit',
            'Asset Manager',
            'Asset Manager',
            'manage_options',
            'optikit-asset-manager',
            [ $this, 'render_settings_page' ]
        );
    }
    
    /**
     * CSS injektálása az admin felület fejlécébe a toggle kapcsolók stilizálásához.
     */
    public function inject_admin_css() {
        // Csak a mi beállítási oldalunkon jelenjen meg a CSS
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'optikit-asset-manager' ) {
            return;
        }
        ?>
        <style>
            .optikit-toggle-switch { position: relative; display: inline-block; width: 50px; height: 28px; }
            .optikit-toggle-switch input { opacity: 0; width: 0; height: 0; }
            .optikit-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 28px; }
            .optikit-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
            input:checked + .optikit-slider { background-color: #2196F3; }
            input:checked + .optikit-slider:before { transform: translateX(22px); }
        </style>
        <?php
    }

    /**
     * Regisztrálja a modul beállításait.
     */
    public function settings_init() {
        register_setting( 'optikit_asset_manager_group', 'optikit_disabled_assets' );
    }
    
    /**
     * A frontend oldalon letiltja a kiválasztott erőforrásokat.
     */
    public function dequeue_selected_assets() {
        // BIZTONSÁGI FUNKCIÓ: Ha a ?optikit_safe_mode=1 paraméter szerepel az URL-ben, ne tiltsunk semmit.
        if ( isset( $_GET['optikit_safe_mode'] ) && $_GET['optikit_safe_mode'] === '1' ) {
            return;
        }

        if ( is_admin() ) {
            return;
        }
        $disabled_assets = get_option( 'optikit_disabled_assets', [] );
        if ( ! empty( $disabled_assets ) && is_array( $disabled_assets ) ) {
            foreach ( $disabled_assets as $handle ) {
                wp_dequeue_style( $handle );
                wp_dequeue_script( $handle );
            }
        }
    }
    
    /**
     * Megpróbálja megbecsülni az erőforrás forrását (plugin/téma/core) az URL alapján.
     */
    private function get_asset_source( $src ) {
        if ( empty($src) ) return 'Ismeretlen';
        if ( strpos( $src, '/wp-content/plugins/' ) !== false ) {
            preg_match( '/\/wp-content\/plugins\/([^\/]+)/', $src, $matches );
            return 'Plugin: ' . ( $matches[1] ?? 'Ismeretlen' );
        }
        if ( strpos( $src, '/wp-content/themes/' ) !== false ) {
            preg_match( '/\/wp-content\/themes\/([^\/]+)/', $src, $matches );
            return 'Téma: ' . ( $matches[1] ?? 'Ismeretlen' );
        }
        if ( strpos( $src, '/wp-includes/' ) !== false || strpos( $src, '/wp-admin/' ) !== false ) {
            return 'WordPress Core';
        }
        return 'Külső / Egyéb';
    }

    /**
     * Visszaadja a helyi fájl méretét olvasható formátumban.
     */
    private function get_local_file_size( $src ) {
        if ( empty($src) || strpos($src, site_url()) === false ) {
            return 'N/A';
        }
        $path = trailingslashit( ABSPATH ) . str_replace( trailingslashit( site_url() ), '', $src );
        if ( file_exists( $path ) ) {
            return size_format( filesize( $path ), 2 );
        }
        return 'N/A';
    }

    /**
     * Megjeleníti a modul beállítási oldalának HTML tartalmát.
     */
    public function render_settings_page() {
        global $wp_scripts, $wp_styles;
        $disabled_assets = get_option( 'optikit_disabled_assets', [] );
        ?>
        <div class="wrap">
            <h1>OptiKit - Erőforrás Kezelő (Asset Manager)</h1>
            <p>Itt kiválaszthatod, mely CSS és JavaScript fájlokat szeretnéd letiltani a weboldaladon.</p>
            
            <div style="border: 1px solid #c3c4c7; border-left: 4px solid #d63638; padding: 10px 15px; margin-bottom: 20px;">
                <h3 style="margin-top: 0;">Biztonsági Funkció</h3>
                <p><strong>FIGYELEM:</strong> Egy fontos fájl letiltása tönkreteheti a weboldalad. Ha az oldal elromlik és nem éred el az admin felületet, használd az alábbi "biztonsági URL"-t. Ez ideiglenesen kikapcsolja a tiltást, amíg vissza nem állítod a hibás beállítást.</p>
                <p><strong>Mentsd el ezt a linket:</strong> <a href="<?php echo esc_url( admin_url('?optikit_safe_mode=1') ); ?>"><?php echo esc_url( admin_url('?optikit_safe_mode=1') ); ?></a></p>
            </div>

            <form action="options.php" method="post">
                <?php settings_fields( 'optikit_asset_manager_group' ); ?>
                
                <?php foreach ( [ 'JavaScript' => $wp_scripts, 'CSS' => $wp_styles ] as $type => $assets ) : ?>
                    <h2 style="margin-top: 30px;"><?php echo $type; ?> Fájlok</h2>
                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <th style="width: 70px;">Állapot</th>
                                <th>Azonosító (Handle)</th>
                                <th>Forrás</th>
                                <th>Méret</th>
                                <th>URL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $assets->registered as $handle => $details ) : ?>
                            <tr>
                                <td>
                                    <label class="optikit-toggle-switch">
                                        <input type="checkbox" name="optikit_disabled_assets[]" value="<?php echo esc_attr( $handle ); ?>" <?php checked( in_array( $handle, $disabled_assets ) ); ?>>
                                        <span class="optikit-slider"></span>
                                    </label>
                                </td>
                                <td><strong><?php echo esc_html( $handle ); ?></strong></td>
                                <td><?php echo esc_html( $this->get_asset_source( $details->src ) ); ?></td>
                                <td><?php echo esc_html( $this->get_local_file_size( $details->src ) ); ?></td>
                                <td><?php if (!empty($details->src)) echo '<code>' . esc_url( $details->src ) . '</code>'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>

                <?php submit_button( 'Változtatások mentése' ); ?>
            </form>
        </div>
        <?php
    }
}

// Elindítjuk a modult
new OptiKit_Asset_Manager();