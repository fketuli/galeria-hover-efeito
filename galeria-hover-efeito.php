<?php
/**
 * Plugin Name:       Galeria com Efeito Hover
 * Description:       Cria uma galeria de imagens 3x3 responsiva com efeito de zoom e texto no hover. Use o shortcode [efeito_galeria_hover].
 * Version:           1.0
 * Author:            Fiama Ketuli
 */

if (!defined('ABSPATH')) {
    exit; // Acesso direto negado
}

class EfeitoGaleriaHover {

    private static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Adiciona a página de administração
        add_action('admin_menu', [$this, 'adicionar_pagina_admin']);
        // Registra e enfileira scripts e estilos do admin
        add_action('admin_enqueue_scripts', [$this, 'enfileirar_scripts_admin']);
        // Registra e enfileira estilos do frontend
        add_action('wp_enqueue_scripts', [$this, 'enfileirar_estilos_frontend']);
        // Registra o shortcode
        add_shortcode('efeito_galeria_hover', [$this, 'renderizar_shortcode']);
        // Salva os dados da galeria
        add_action('admin_init', [$this, 'salvar_dados_galeria']);
    }

    public function adicionar_pagina_admin() {
        add_menu_page(
            'Galeria Hover',
            'Galeria Hover',
            'manage_options',
            'galeria-hover-settings',
            [$this, 'renderizar_pagina_admin'],
            'dashicons-format-gallery',
            25
        );
    }

    public function enfileirar_scripts_admin($hook) {
        // Carrega scripts apenas na página do nosso plugin
        if ('toplevel_page_galeria-hover-settings' != $hook) {
            return;
        }
        wp_enqueue_media(); // Essencial para a biblioteca de mídia do WP
        wp_enqueue_script(
            'galeria-hover-admin-js',
            plugin_dir_url(__FILE__) . 'js/admin-galeria.js',
            ['jquery', 'jquery-ui-sortable'],
            '1.0',
            true
        );
        wp_localize_script('galeria-hover-admin-js', 'gallery_data', [
            'nonce' => wp_create_nonce('galeria_hover_nonce')
        ]);
        wp_enqueue_style(
            'galeria-hover-admin-css',
            plugin_dir_url(__FILE__) . 'css/admin-galeria.css'
        );
    }

    public function enfileirar_estilos_frontend() {
        // Carrega o CSS no site apenas quando o shortcode é usado
        if (has_shortcode(get_post()->post_content, 'efeito_galeria_hover')) {
            wp_enqueue_style(
                'galeria-hover-frontend-css',
                plugin_dir_url(__FILE__) . 'css/estilo-galeria.css'
            );
        }
    }

    public function salvar_dados_galeria() {
        if (isset($_POST['galeria_hover_nonce']) && wp_verify_nonce($_POST['galeria_hover_nonce'], 'salvar_galeria_hover')) {
            if (current_user_can('manage_options')) {
                $gallery_items = [];
                if (isset($_POST['gallery_image_id'])) {
                    for ($i = 0; $i < count($_POST['gallery_image_id']); $i++) {
                        if (!empty($_POST['gallery_image_id'][$i])) {
                            $gallery_items[] = [
                                'id' => intval($_POST['gallery_image_id'][$i]),
                                'text' => sanitize_text_field($_POST['gallery_image_text'][$i]),
                            ];
                        }
                    }
                }
                update_option('opcoes_galeria_hover', $gallery_items);
            }
        }
    }


    public function renderizar_pagina_admin() {
        ?>
        <div class="wrap">
            <h1>Gerenciar Galeria com Efeito Hover</h1>
            <p>Adicione, remova e reordene as imagens da sua galeria. O limite ideal é de 9 imagens para manter o layout 3x3.</p>

            <form method="POST" action="">
                <input type="hidden" name="galeria_hover_nonce" value="<?php echo wp_create_nonce('salvar_galeria_hover'); ?>">

                <div id="galeria-admin-container">
                    <?php
                    $gallery_items = get_option('opcoes_galeria_hover', []);
                    if (!empty($gallery_items)) {
                        foreach ($gallery_items as $item) {
                            $image_url = wp_get_attachment_thumb_url($item['id']);
                            ?>
                            <div class="galeria-item-admin">
                                <img src="<?php echo esc_url($image_url); ?>" width="100" height="100">
                                <div class="galeria-item-fields">
                                    <label>Texto da Categoria:</label>
                                    <input type="text" name="gallery_image_text[]" value="<?php echo esc_attr($item['text']); ?>" placeholder="Ex: Temática Infantil">
                                    <input type="hidden" name="gallery_image_id[]" value="<?php echo esc_attr($item['id']); ?>">
                                    <button type="button" class="button button-danger remover-imagem">Remover</button>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>

                <p>
                    <button type="button" id="adicionar-imagem" class="button button-primary">Adicionar Imagem</button>
                    <?php submit_button('Salvar Galeria'); ?>
                </p>
            </form>
        </div>
        <?php
    }

    public function renderizar_shortcode() {
        $gallery_items = get_option('opcoes_galeria_hover', []);

        if (empty($gallery_items)) {
            return '<p>Nenhuma imagem foi adicionada à galeria ainda.</p>';
        }

        ob_start();
        ?>
        <div class="galeria-hover-grid">
            <?php foreach ($gallery_items as $item) : ?>
                <?php
                $image_url = wp_get_attachment_image_url($item['id'], 'large'); // Pega uma imagem de tamanho grande
                $image_alt = get_post_meta($item['id'], '_wp_attachment_image_alt', true);
                if (empty($image_alt)) {
                    $image_alt = $item['text']; // Usa o texto como fallback para o alt
                }
                ?>
                <div class="galeria-item group">
                    <div class="aspect-square">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>" class="galeria-imagem">
                    </div>
                    <div class="galeria-overlay">
                        <div class="galeria-texto-wrapper">
                            <span class="galeria-texto-categoria"><?php echo esc_html($item['text']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

EfeitoGaleriaHover::get_instance();