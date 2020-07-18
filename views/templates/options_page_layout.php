<?php global $pluginName; ?>
<div class="wrap">
    <h1><?= $pluginName ?></h1>
    <h2>Configurações Gerais do <?= $pluginName ?></h2>
    <form method="post" action="options.php">
        <?php
            settings_fields( 'freteclick_options_page' );
            do_settings_sections( 'freteclick_options_page' );
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="FC_API_KEY"><?= __( 'Chave da API' ) ?></label>
                </th>
                <td>
                    <input name="FC_API_KEY" type="text" id="FC_API_KEY" value="<?= get_option("FC_API_KEY") ?>" class="regular-text">
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="freteclick_display_product"><?= __('Cálculo de Frete na página do Produto') ?></label>
                </th>
                <td>
                    <select id="freteclick_display_product" name="freteclick_display_product">
                        <?php $vl = get_option('freteclick_display_product'); $sl = " selected='selected'"; ?>
                        <option value="0"<?= $vl == 0 ? $sl : "" ?>><?= __("Não") ?></option>
                        <option value="1"<?= $vl == 1 ? $sl : "" ?>><?= __("Sim") ?></option>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="freteclick_display_product"><?= __('Prazo extra em dias opcional') ?></label>
                </th>
                <td>
                    <input name="FC_PRAZO_EXTRA" type="text" id="FC_PRAZO_EXTRA" value="<?= get_option("FC_PRAZO_EXTRA") ?>" >
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="freteclick_display_product"><?= __('Prazo variado em dias opcional') ?></label>
                </th>
                <td>
                    <input name="FC_PRAZO_VARIADO" type="text" id="FC_PRAZO_VARIADO" value="<?= get_option("FC_PRAZO_VARIADO") ?>" >
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="freteclick_quote_type"><?= __('Tipo de Cotação') ?></label>
                    <p>
                        <strong>Cotação Completa:</strong> Mostra todas as opções de frete disponíveis<br/>
                        <strong>Cotação Simples:</strong> Mostra apenas as opções de frete mais rápida e mais econômica
                    </p>
                </th>
                <td>
                    <select id="freteclick_quote_type" name="freteclick_quote_type">
                        <?php $vl = get_option('freteclick_quote_type'); $sl = " selected='selected'"; ?>
                        <option value="full"<?= $vl == "full" ? $sl : "" ?>><?= __("Cotação Completa") ?></option>
                        <option value="simple"<?= $vl == "simple" ? $sl : "" ?>><?= __("Cotação Simples") ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>