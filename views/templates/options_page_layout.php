<?php global $pluginName; ?>
<div class="wrap">
    <h1><?= $pluginName ?></h1>
    <h2>Configurações Gerais do <?= $pluginName ?></h2>
    <form method="post" action="options.php">
        <?php
            settings_fields( 'freteclick_options_page' );
            do_settings_sections( 'freteclick_options_page' );
        ?>
        <table>
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
        </table>
        <?php submit_button(); ?>
    </form>
</div>