jQuery(document).ready(function($) {
    var mediaUploader;
    var container = $('#galeria-admin-container');

    // Abre o seletor de mídia do WordPress
    $('#adicionar-imagem').on('click', function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Escolha as Imagens para a Galeria',
            button: {
                text: 'Selecionar'
            },
            multiple: true // Permite selecionar várias imagens
        });

        mediaUploader.on('select', function() {
            var attachments = mediaUploader.state().get('selection').toJSON();
            attachments.forEach(function(attachment) {
                // Adiciona a nova imagem na interface do admin
                container.append(
                    `<div class="galeria-item-admin">
                        <img src="${attachment.sizes.thumbnail.url}" width="100" height="100">
                        <div class="galeria-item-fields">
                            <label>Texto da Categoria:</label>
                            <input type="text" name="gallery_image_text[]" value="" placeholder="Ex: Temática Infantil">
                            <input type="hidden" name="gallery_image_id[]" value="${attachment.id}">
                            <button type="button" class="button button-danger remover-imagem">Remover</button>
                        </div>
                    </div>`
                );
            });
        });
        mediaUploader.open();
    });

    // Remove uma imagem
    container.on('click', '.remover-imagem', function() {
        $(this).closest('.galeria-item-admin').remove();
    });

    // Torna os itens arrastáveis para reordenar
    container.sortable();

});

// Adiciona um CSS básico para a página de administração
(function() {
    var css = `
        #galeria-admin-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-top: 20px;
            border: 1px dashed #ccc;
            padding: 15px;
        }
        .galeria-item-admin {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #f9f9f9;
            padding: 10px;
            border: 1px solid #ddd;
            cursor: move;
        }
        .galeria-item-admin img {
            object-fit: cover;
        }
        .galeria-item-fields {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex-grow: 1;
        }
        .galeria-item-fields input[type="text"] {
            width: 100%;
        }
        .galeria-item-fields .remover-imagem {
            align-self: flex-start;
        }
    `;
    var style = document.createElement('style');
    style.type = 'text/css';
    style.appendChild(document.createTextNode(css));
    document.head.appendChild(style);
})();