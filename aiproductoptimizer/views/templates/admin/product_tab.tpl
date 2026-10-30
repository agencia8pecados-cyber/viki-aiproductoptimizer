{**
 * AI Product Optimizer - Pestaña en edición de producto
 *}

<div class="panel" id="ai-product-optimizer">
    <div class="panel-heading">
        <i class="icon-magic"></i> {l s='AI Product Optimizer' mod='aiproductoptimizer'}
        <span class="badge badge-info">{$provider|escape:'html':'UTF-8'|upper}</span>
    </div>
    
    <div class="panel-body">
        <div class="alert alert-info">
            <i class="icon-info-circle"></i>
            {l s='Optimiza automáticamente el contenido de este producto usando Inteligencia Artificial. Puedes optimizar todos los campos a la vez o cada uno por separado.' mod='aiproductoptimizer'}
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="btn-group mb-3">
                    <button type="button" class="btn btn-primary" id="btn-optimize-all">
                        <i class="icon-magic"></i> {l s='Optimizar Todo' mod='aiproductoptimizer'}
                    </button>
                    <button type="button" class="btn btn-default" id="btn-refresh-original">
                        <i class="icon-refresh"></i> {l s='Recargar Original' mod='aiproductoptimizer'}
                    </button>
                </div>
            </div>
        </div>

        <div id="ai-loading" class="text-center" style="display: none;">
            <i class="icon-spinner icon-spin icon-3x"></i>
            <p>{l s='Generando contenido optimizado...' mod='aiproductoptimizer'}</p>
        </div>

        <div id="ai-results" style="display: none;">
            <!-- Título -->
            <div class="form-group row ai-field-group">
                <label class="control-label col-lg-2">
                    {l s='Título' mod='aiproductoptimizer'}
                </label>
                <div class="col-lg-5">
                    <div class="ai-field-header">
                        <span class="badge badge-secondary">{l s='Original' mod='aiproductoptimizer'}</span>
                    </div>
                    <input type="text" class="form-control" id="ai-original-title" readonly>
                </div>
                <div class="col-lg-5">
                    <div class="ai-field-header">
                        <span class="badge badge-success">{l s='Optimizado' mod='aiproductoptimizer'}</span>
                        <button type="button" class="btn btn-xs btn-default btn-regenerate" data-field="title">
                            <i class="icon-refresh"></i>
                        </button>
                    </div>
                    <input type="text" class="form-control ai-optimized-field" id="ai-optimized-title" name="ai_title">
                    <span class="char-count"></span>
                </div>
            </div>

            <!-- Resumen / Descripción corta -->
            <div class="form-group row ai-field-group">
                <label class="control-label col-lg-2">
                    {l s='Resumen' mod='aiproductoptimizer'}
                </label>
                <div class="col-lg-5">
                    <div class="ai-field-header">
                        <span class="badge badge-secondary">{l s='Original' mod='aiproductoptimizer'}</span>
                    </div>
                    <textarea class="form-control" id="ai-original-short" rows="3" readonly></textarea>
                </div>
                <div class="col-lg-5">
                    <div class="ai-field-header">
                        <span class="badge badge-success">{l s='Optimizado' mod='aiproductoptimizer'}</span>
                        <button type="button" class="btn btn-xs btn-default btn-regenerate" data-field="short_description">
                            <i class="icon-refresh"></i>
                        </button>
                    </div>
                    <textarea class="form-control ai-optimized-field" id="ai-optimized-short" name="ai_short_description" rows="3"></textarea>
                    <span class="char-count"></span>
                </div>
            </div>

            <!-- Descripción larga -->
            <div class="form-group row ai-field-group">
                <label class="control-label col-lg-2">
                    {l s='Descripción' mod='aiproductoptimizer'}
                </label>
                <div class="col-lg-5">
                    <div class="ai-field-header">
                        <span class="badge badge-secondary">{l s='Original' mod='aiproductoptimizer'}</span>
                    </div>
                    <textarea class="form-control" id="ai-original-description" rows="8" readonly></textarea>
                </div>
                <div class="col-lg-5">
                    <div class="ai-field-header">
                        <span class="badge badge-success">{l s='Optimizado' mod='aiproductoptimizer'}</span>
                        <button type="button" class="btn btn-xs btn-default btn-regenerate" data-field="description">
                            <i class="icon-refresh"></i>
                        </button>
                    </div>
                    <textarea class="form-control ai-optimized-field" id="ai-optimized-description" name="ai_description" rows="8"></textarea>
                </div>
            </div>

            <!-- Meta Título -->
            <div class="form-group row ai-field-group">
                <label class="control-label col-lg-2">
                    {l s='Meta Título' mod='aiproductoptimizer'}
                </label>
                <div class="col-lg-5">
                    <div class="ai-field-header">
                        <span class="badge badge-secondary">{l s='Original' mod='aiproductoptimizer'}</span>
                    </div>
                    <input type="text" class="form-control" id="ai-original-meta-title" readonly>
                </div>
                <div class="col-lg-5">
                    <div class="ai-field-header">
                        <span class="badge badge-success">{l s='Optimizado' mod='aiproductoptimizer'}</span>
                        <button type="button" class="btn btn-xs btn-default btn-regenerate" data-field="meta_title">
                            <i class="icon-refresh"></i>
                        </button>
                    </div>
                    <input type="text" class="form-control ai-optimized-field" id="ai-optimized-meta-title" name="ai_meta_title" maxlength="70">
                    <span class="char-count"><span class="current">0</span>/60</span>
                </div>
            </div>

            <!-- Meta Descripción -->
            <div class="form-group row ai-field-group">
                <label class="control-label col-lg-2">
                    {l s='Meta Descripción' mod='aiproductoptimizer'}
                </label>
                <div class="col-lg-5">
                    <div class="ai-field-header">
                        <span class="badge badge-secondary">{l s='Original' mod='aiproductoptimizer'}</span>
                    </div>
                    <textarea class="form-control" id="ai-original-meta-desc" rows="2" readonly></textarea>
                </div>
                <div class="col-lg-5">
                    <div class="ai-field-header">
                        <span class="badge badge-success">{l s='Optimizado' mod='aiproductoptimizer'}</span>
                        <button type="button" class="btn btn-xs btn-default btn-regenerate" data-field="meta_description">
                            <i class="icon-refresh"></i>
                        </button>
                    </div>
                    <textarea class="form-control ai-optimized-field" id="ai-optimized-meta-desc" name="ai_meta_description" rows="2" maxlength="160"></textarea>
                    <span class="char-count"><span class="current">0</span>/160</span>
                </div>
            </div>

            <!-- Keywords sugeridas -->
            <div class="form-group row ai-field-group" id="ai-keywords-group" style="display: none;">
                <label class="control-label col-lg-2">
                    {l s='Keywords Sugeridas' mod='aiproductoptimizer'}
                </label>
                <div class="col-lg-10">
                    <div id="ai-suggested-keywords"></div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="form-group row">
                <div class="col-lg-12">
                    <hr>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success btn-lg" id="btn-apply-all">
                            <i class="icon-check"></i> {l s='Aplicar Todos los Cambios' mod='aiproductoptimizer'}
                        </button>
                        <button type="button" class="btn btn-warning" id="btn-apply-selected">
                            <i class="icon-check-square-o"></i> {l s='Aplicar Seleccionados' mod='aiproductoptimizer'}
                        </button>
                        <button type="button" class="btn btn-default" id="btn-cancel">
                            <i class="icon-times"></i> {l s='Cancelar' mod='aiproductoptimizer'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var aiOptAjaxUrl = '{$ajax_url|escape:'javascript':'UTF-8'}';
    var aiOptProductId = {$id_product|intval};
    var aiOptLangId = {$id_lang|intval};
</script>
