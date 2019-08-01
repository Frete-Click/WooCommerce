<div class="wrap">
    <h1>Frete Click</h1>
    <h2>Configurações Gerais do Frete Click</h2>
    <form method="post" action="options.php">
        <?php
            settings_fields( 'freteclick_options_page' );
            do_settings_sections( 'freteclick_options_page' );
        ?>
        <table>
            <tr valign="top">
                <th scope="row">
                    <label for="freteclick_display_product">Cálculo de Frete na página do Produto</label>
                </th>
                <td>
                    <select id="freteclick_display_product" name="freteclick_display_product">
                        <?php $vl = get_option('freteclick_display_product'); $sl = " selected='selected'"; ?>
                        <option value="0"<?= $vl == 0 ? $sl : "" ?>>Não</option>
                        <option value="1"<?= $vl == 1 ? $sl : "" ?>>Sim</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>