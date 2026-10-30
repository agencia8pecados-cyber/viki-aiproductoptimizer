{**
 * AI Product Optimizer - Lista de productos con filtros y paginación
 * @version 1.2.0
 *}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-magic"></i> {l s='AI Product Optimizer - Optimización Masiva' mod='aiproductoptimizer'}
        <span class="badge badge-info" id="total-products-badge">{$pagination.total_products|intval} {l s='productos' mod='aiproductoptimizer'}</span>
    </div>
    
    <div class="panel-body">
        <div class="alert alert-info">
            <i class="icon-info-circle"></i>
            {l s='Selecciona los productos que quieres optimizar con IA. Puedes filtrar por estado, marca, categoría y estado de optimización.' mod='aiproductoptimizer'}
        </div>

        <!-- FILTROS -->
        <div class="well" id="filters-panel">
            <div class="row">
                <div class="col-lg-2">
                    <div class="form-group">
                        <label>{l s='Buscar:' mod='aiproductoptimizer'}</label>
                        <input type="text" class="form-control" id="filter-search" placeholder="{l s='ID, nombre o referencia' mod='aiproductoptimizer'}" value="{$filters.search|escape:'html':'UTF-8'}">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group">
                        <label>{l s='Estado:' mod='aiproductoptimizer'}</label>
                        <select class="form-control" id="filter-active">
                            <option value="">{l s='Todos' mod='aiproductoptimizer'}</option>
                            <option value="1" {if $filters.active === '1'}selected{/if}>{l s='Solo activos' mod='aiproductoptimizer'}</option>
                            <option value="0" {if $filters.active === '0'}selected{/if}>{l s='Solo inactivos' mod='aiproductoptimizer'}</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group">
                        <label>{l s='Marca:' mod='aiproductoptimizer'}</label>
                        <select class="form-control" id="filter-manufacturer">
                            <option value="0">{l s='Todas las marcas' mod='aiproductoptimizer'}</option>
                            {foreach from=$manufacturers item=manufacturer}
                                <option value="{$manufacturer.id_manufacturer|intval}" {if $filters.id_manufacturer == $manufacturer.id_manufacturer}selected{/if}>
                                    {$manufacturer.name|escape:'html':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="form-group">
                        <label>{l s='Categoría:' mod='aiproductoptimizer'}</label>
                        <select class="form-control" id="filter-category">
                            <option value="0">{l s='Todas las categorías' mod='aiproductoptimizer'}</option>
                            {foreach from=$categories item=category}
                                <option value="{$category.id_category|intval}" {if $filters.id_category == $category.id_category}selected{/if}>
                                    {$category.name|escape:'html':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group">
                        <label>{l s='Optimizado:' mod='aiproductoptimizer'}</label>
                        <select class="form-control" id="filter-optimized">
                            <option value="">{l s='Todos' mod='aiproductoptimizer'}</option>
                            <option value="1" {if $filters.optimized === '1'}selected{/if}>{l s='Optimizados' mod='aiproductoptimizer'}</option>
                            <option value="0" {if $filters.optimized === '0'}selected{/if}>{l s='Sin optimizar' mod='aiproductoptimizer'}</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-1">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-default btn-block" id="btn-clear-filters" title="{l s='Limpiar filtros' mod='aiproductoptimizer'}">
                            <i class="icon-eraser"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- OPCIONES DE OPTIMIZACIÓN -->
        <div class="row mb-3">
            <div class="col-lg-6">
                <div class="form-group">
                    <label>{l s='Campos a optimizar:' mod='aiproductoptimizer'}</label>
                    <div class="checkbox-group">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="fields[]" value="title" checked> {l s='Título' mod='aiproductoptimizer'}
                        </label>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="fields[]" value="short_description" checked> {l s='Resumen' mod='aiproductoptimizer'}
                        </label>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="fields[]" value="description" checked> {l s='Descripción' mod='aiproductoptimizer'}
                        </label>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="fields[]" value="meta_title" checked> {l s='Meta Título' mod='aiproductoptimizer'}
                        </label>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="fields[]" value="meta_description" checked> {l s='Meta Descripción' mod='aiproductoptimizer'}
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="form-group">
                    <label>{l s='Idioma:' mod='aiproductoptimizer'}</label>
                    <select name="id_lang" class="form-control" id="bulk-lang-select">
                        {foreach from=$languages item=lang}
                            <option value="{$lang.id_lang|intval}" {if $lang.id_lang == $default_lang}selected{/if}>
                                {$lang.name|escape:'html':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-primary btn-block" id="btn-bulk-optimize">
                        <i class="icon-magic"></i> {l s='Optimizar Seleccionados' mod='aiproductoptimizer'}
                    </button>
                </div>
            </div>
        </div>

        <!-- BARRA DE PROGRESO -->
        <div id="bulk-progress" style="display: none;">
            <div class="progress">
                <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%;">
                    <span class="progress-text">0%</span>
                </div>
            </div>
            <p class="text-center" id="bulk-status"></p>
        </div>

        <!-- LOADING OVERLAY -->
        <div id="table-loading" style="display: none;">
            <div class="text-center" style="padding: 40px;">
                <i class="icon-spinner icon-spin icon-3x"></i>
                <p>{l s='Cargando productos...' mod='aiproductoptimizer'}</p>
            </div>
        </div>

        <!-- TABLA DE PRODUCTOS -->
        <div class="table-responsive" id="table-container">
            <table class="table table-striped table-hover" id="products-table">
                <thead>
                    <tr>
                        <th width="30">
                            <input type="checkbox" id="select-all">
                        </th>
                        <th width="60">{l s='ID' mod='aiproductoptimizer'}</th>
                        <th>{l s='Nombre Original' mod='aiproductoptimizer'}</th>
                        <th>{l s='Nombre Actual' mod='aiproductoptimizer'}</th>
                        <th width="120">{l s='Marca' mod='aiproductoptimizer'}</th>
                        <th width="150">{l s='Categoría' mod='aiproductoptimizer'}</th>
                        <th width="80" class="text-center">{l s='Estado' mod='aiproductoptimizer'}</th>
                        <th width="100" class="text-center">{l s='Optimizado' mod='aiproductoptimizer'}</th>
                        <th width="120" class="text-center">{l s='Acciones' mod='aiproductoptimizer'}</th>
                    </tr>
                </thead>
                <tbody id="products-tbody">
                    {foreach from=$products item=product}
                        <tr data-id="{$product.id_product|intval}">
                            <td>
                                <input type="checkbox" class="product-checkbox" value="{$product.id_product|intval}">
                            </td>
                            <td>{$product.id_product|intval}</td>
                            <td>
                                {if $product.original_name}
                                    <span class="text-muted">{$product.original_name|escape:'html':'UTF-8'|truncate:60:'...'}</span>
                                {else}
                                    <span class="text-muted">-</span>
                                {/if}
                            </td>
                            <td>
                                <strong>{$product.name|escape:'html':'UTF-8'}</strong>
                                {if $product.is_optimized && $product.original_name && $product.original_name != $product.name}
                                    <span class="label label-success" style="margin-left:5px;"><i class="icon-check"></i></span>
                                {/if}
                                {if empty($product.meta_title) || empty($product.meta_description)}
                                    <span class="label label-warning" title="{l s='Faltan meta datos SEO' mod='aiproductoptimizer'}">
                                        <i class="icon-warning"></i> SEO
                                    </span>
                                {/if}
                            </td>
                            <td>
                                {if $product.manufacturer_name}
                                    <span class="text-muted">{$product.manufacturer_name|escape:'html':'UTF-8'}</span>
                                {else}
                                    <span class="text-muted">-</span>
                                {/if}
                            </td>
                            <td>
                                {if $product.category_name}
                                    <span class="text-muted">{$product.category_name|truncate:25:'...'|escape:'html':'UTF-8'}</span>
                                {else}
                                    <span class="text-muted">-</span>
                                {/if}
                            </td>
                            <td class="text-center">
                                {if $product.active}
                                    <span class="label label-success">{l s='Activo' mod='aiproductoptimizer'}</span>
                                {else}
                                    <span class="label label-danger">{l s='Inactivo' mod='aiproductoptimizer'}</span>
                                {/if}
                            </td>
                            <td class="text-center">
                                {if $product.is_optimized}
                                    <span class="label label-success optimized-badge" data-id="{$product.id_product|intval}" title="{if $product.optimized_date}{l s='Optimizado el' mod='aiproductoptimizer'} {$product.optimized_date|escape:'html':'UTF-8'}{/if}">
                                        <i class="icon-check"></i> {l s='Sí' mod='aiproductoptimizer'}
                                    </span>
                                {else}
                                    <span class="label label-default optimized-badge" data-id="{$product.id_product|intval}">
                                        <i class="icon-times"></i> {l s='No' mod='aiproductoptimizer'}
                                    </span>
                                {/if}
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-xs btn-primary btn-optimize-single" data-id="{$product.id_product|intval}" title="{l s='Optimizar producto' mod='aiproductoptimizer'}">
                                    <i class="icon-magic"></i>
                                </button>
                                <a href="{$link->getAdminLink('AdminProducts', true, ['id_product' => $product.id_product, 'updateproduct' => 1])|escape:'html':'UTF-8'}" class="btn btn-xs btn-default" target="_blank" title="{l s='Editar producto' mod='aiproductoptimizer'}">
                                    <i class="icon-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    {foreachelse}
                        <tr id="no-products-row">
                            <td colspan="9" class="text-center">
                                <p class="text-muted" style="padding: 20px;">
                                    <i class="icon-info-circle"></i> {l s='No se encontraron productos con los filtros seleccionados' mod='aiproductoptimizer'}
                                </p>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>

        <!-- PAGINACIÓN -->
        <div class="row" id="pagination-container">
            <div class="col-lg-6">
                <p class="text-muted pagination-info">
                    {l s='Mostrando' mod='aiproductoptimizer'} 
                    <strong id="showing-from">{if $pagination.total_products > 0}1{else}0{/if}</strong> - 
                    <strong id="showing-to">{if $pagination.total_products < $pagination.per_page}{$pagination.total_products|intval}{else}{$pagination.per_page|intval}{/if}</strong> 
                    {l s='de' mod='aiproductoptimizer'} 
                    <strong id="showing-total">{$pagination.total_products|intval}</strong> 
                    {l s='productos' mod='aiproductoptimizer'}
                </p>
            </div>
            <div class="col-lg-6">
                <nav aria-label="Paginación" class="pull-right">
                    <ul class="pagination" id="pagination">
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Modal para previsualización -->
<div class="modal fade" id="preview-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{l s='Previsualización de Optimización' mod='aiproductoptimizer'} - <span id="preview-product-name"></span></h4>
            </div>
            <div class="modal-body" id="preview-content">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {l s='Cancelar' mod='aiproductoptimizer'}
                </button>
                <button type="button" class="btn btn-success" id="btn-apply-preview">
                    <i class="icon-check"></i> {l s='Aplicar Cambios' mod='aiproductoptimizer'}
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var aiOptAjaxUrl = '{$ajax_url|escape:'javascript':'UTF-8'}';
    var aiOptCurrentPage = {$pagination.current_page|intval};
    var aiOptTotalPages = {$pagination.total_pages|intval};
    var aiOptPerPage = {$pagination.per_page|intval};
    var aiOptTotalProducts = {$pagination.total_products|intval};
</script>
