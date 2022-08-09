<?php global $pluginName; ?>
<div class="wrap">
    <div class="wrap-settings-fc">
        <h1>Frete Click</h1>
        <hr>
        <h2>Configurações</h2>
        <form method="post" action="options.php" class="form-fc">
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
                        <label for="freteclick_display_product"><?= __('Prazo extra em dias opcional') ?></label>
                    </th>
                    <td>
                        <input name="FC_PRAZO_EXTRA" type="text" id="FC_PRAZO_EXTRA" value="<?= get_option("FC_PRAZO_EXTRA") ?>" placeholder="0" class="regular-text">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="freteclick_display_product"><?= __('Prazo variado em dias opcional') ?></label>
                    </th>
                    <td>
                        <input name="FC_PRAZO_VARIADO" type="text" id="FC_PRAZO_VARIADO" value="<?= get_option("FC_PRAZO_VARIADO") ?>" placeholder="0" class="regular-text">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="freteclick_display_product"><?= __('Cálculo de Frete na página do Produto') ?></label>
                    </th>
                    <td>
                        <select id="freteclick_display_product" name="freteclick_display_product" class="regular-text">
                            <?php $vl = get_option('freteclick_display_product'); $sl = " selected='selected'"; ?>
                            <option value="0"<?= $vl == 0 ? $sl : "" ?>><?= __("Não") ?></option>
                            <option value="1"<?= $vl == 1 ? $sl : "" ?>><?= __("Sim") ?></option>
                        </select>
                    </td>
                </tr>                
                <tr valign="top">
                    <th scope="row">
                        <label for="freteclick_noretrieve"><?= __('Exibir transportadoras sem coletas') ?></label>
                    </th>
                    <td>
                        <select id="freteclick_noretrieve" name="freteclick_noretrieve" class="regular-text">
                            <?php $vl = get_option('freteclick_noretrieve'); $sl = " selected='selected'"; ?>
                            <option value="0"<?= $vl == 0 ? $sl : "" ?>><?= __("Não") ?></option>
                            <option value="1"<?= $vl == 1 ? $sl : "" ?>><?= __("Sim") ?></option>
                        </select>
                    </td>
                </tr>            
                <tr valign="top">
                    <th scope="row">
                        <label for="freteclick_quote_type"><?= __('Tipo de Cotação') ?></label>
                    </th>
                    <td>
                        <select id="freteclick_quote_type" name="freteclick_quote_type" class="regular-text">
                            <?php $vl = get_option('freteclick_quote_type'); $sl = " selected='selected'"; ?>
                            <option value="full"<?= $vl == "full" ? $sl : "" ?>><?= __("Cotação Completa") ?></option>
                            <option value="simple"<?= $vl == "simple" ? $sl : "" ?>><?= __("Cotação Simples") ?></option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="fclick_invoice"><?= __('Nota Fiscal') ?></label>
                    </th>
                    <td>
                        <div class="regular-text">
                            <p class="fclick-text">Será somado na nota fiscal o valor do frete?</p>
                        </div>
                        <select id="fclick_invoice" name="fclick_invoice" class="regular-text">
                            <?php $vl = get_option('fclick_invoice'); $sl = " selected='selected'"; ?>
                            <option value="0"<?= $vl == 0 ? $sl : "" ?>><?= __("Não") ?></option>
                            <option value="1"<?= $vl == 1 ? $sl : "" ?>><?= __("Sim") ?></option>
                        </select>
                    </td>
                </tr> 
                <tr valign="top">
                    <th scope="row">
                        <label for="fclick_deny_carriers"><?= __('Restrições de transportadoras') ?></label>
                    </th>
                    <td>
                        <div class="regular-text">
                            <p class="fclick-text">Para restringir uma transportadora é preciso solicitar ID da mesma em nossa <a target="_black" href="https://www.freteclick.com.br/">central de relacionamento!</a></p>
                            <p class="fclick-text">Restringir mais de uma transportadora cadastre separando por vírgula!</p>

                        </div>
                        <textarea name="fclick_deny_carriers" type="text" id="fclick_deny_carriers" placeholder="1,2,3" class="regular-text"><?= get_option('fclick_deny_carriers') ?></textarea>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

    </div>
</div>