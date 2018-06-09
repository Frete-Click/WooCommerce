{*
 * Módulo para o calculo do frete usando o webservice do FreteClick
 *  @author    Ederson Ferreira (ederson.dev@gmail.com)
 *  @copyright 2010-2015 FreteClick
 *  @license   LICENSE
 *}
<div id="box-frete-click" class="panel panel-info">
    <div class="panel-heading">Frete Click</div>
    <div class="panel-body">
        <form name="calcular_frete" id="calcular_frete" data-action="{$url_shipping_quote|escape:'htmlall':'UTF-8'}" method="post" />
        <input type="hidden" name="city-origin-id" value="{$city_origin_id|escape:'htmlall':'UTF-8'}" />           
        <input type="hidden" name="cep-origin" value="{$cep_origin|escape:'htmlall':'UTF-8'}" /> 
        <input type="text" id="fk-cep"  value="{$cep|escape:'htmlall':'UTF-8'}" onkeypress="maskCep(this, '#####-###')" maxlength="9" class="form-control" name="cep" placeholder="CEP de destino" required>
        <input type="hidden" name="product-type" value="{$product->name|escape:'htmlall':'UTF-8'}" />
        <input type="hidden" name="product-total-price" id="product-total-price" data-value="{$product->price|escape:'htmlall':'UTF-8'}" value="{$product->price|escape:'htmlall':'UTF-8'}" />
        <input type="hidden" name="product-package[0][qtd]" value="1" />
        <input type="hidden" name="product-package[0][weight]" value="{number_format($product->weight, 10, ',', '')|escape:'htmlall':'UTF-8'}" />
        <input type="hidden" name="product-package[0][height]" value="{number_format($product->height/100, 10, ',', '')|escape:'htmlall':'UTF-8'}" />
        <input type="hidden" name="product-package[0][width]" value="{number_format($product->width/100, 10, ',', '')|escape:'htmlall':'UTF-8'}" />
        <input type="hidden" name="product-package[0][depth]" value="{number_format($product->depth/100, 10, ',', '')|escape:'htmlall':'UTF-8'}" />        
        <button class="btn btn-default" type="button" id="btCalcularFrete" data-loading-text="Carregando...">Calcular</button>
        </form>
        <div id="resultado-frete" style="padding-top:20px;">
            <table class="table" id="frete-valores">
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>