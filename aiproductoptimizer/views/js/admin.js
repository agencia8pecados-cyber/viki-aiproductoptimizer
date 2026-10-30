/**
 * AI Product Optimizer - JavaScript Admin
 * @author    OKVIKI <https://okviki.com>
 */

$(document).ready(function() {
    
    var currentPage = typeof aiOptCurrentPage !== 'undefined' ? aiOptCurrentPage : 1;
    var totalPages = typeof aiOptTotalPages !== 'undefined' ? aiOptTotalPages : 1;
    var perPage = typeof aiOptPerPage !== 'undefined' ? aiOptPerPage : 50;
    var totalProducts = typeof aiOptTotalProducts !== 'undefined' ? aiOptTotalProducts : 0;
    var filterTimeout = null;

    renderPagination();

    // PESTAÑA EN EDICIÓN DE PRODUCTO
    $('#btn-optimize-all').on('click', function() {
        optimizeProduct();
    });
    
    $('#btn-refresh-original').on('click', function() {
        loadOriginalData();
    });
    
    $('.btn-regenerate').on('click', function() {
        var field = $(this).data('field');
        regenerateField(field);
    });
    
    $('#btn-apply-all').on('click', function() {
        applyChanges(getAllOptimizedData());
    });
    
    $('#btn-apply-selected').on('click', function() {
        var data = getModifiedData();
        if (Object.keys(data).length === 0) {
            showNotification('No hay cambios para aplicar', 'warning');
            return;
        }
        applyChanges(data);
    });
    
    $('#btn-cancel').on('click', function() {
        $('#ai-results').hide();
    });
    
    $('.ai-optimized-field').on('input', function() {
        updateCharCount($(this));
    });
    
    // LISTA DE PRODUCTOS - FILTROS
    $('#filter-search').on('keyup', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function() {
            currentPage = 1;
            loadProducts();
        }, 500);
    });

    $('#filter-active, #filter-manufacturer, #filter-category, #filter-optimized').on('change', function() {
        currentPage = 1;
        loadProducts();
    });

    $('#btn-clear-filters').on('click', function() {
        $('#filter-search').val('');
        $('#filter-active').val('');
        $('#filter-manufacturer').val('0');
        $('#filter-category').val('0');
        $('#filter-optimized').val('');
        currentPage = 1;
        loadProducts();
    });
    
    // PAGINACIÓN
    $(document).on('click', '#pagination a', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        
        if (page && page >= 1 && page <= totalPages && page != currentPage) {
            currentPage = page;
            loadProducts();
        }
    });

    // SELECCIÓN
    $('#select-all').on('change', function() {
        $('.product-checkbox').prop('checked', $(this).is(':checked'));
    });

    $(document).on('change', '.product-checkbox', function() {
        if (!$(this).is(':checked')) {
            $('#select-all').prop('checked', false);
        } else {
            var allChecked = $('.product-checkbox:not(:checked)').length === 0;
            $('#select-all').prop('checked', allChecked);
        }
    });
    
    // OPTIMIZACIÓN
    $('#btn-bulk-optimize').on('click', function() {
        var selectedIds = [];
        $('.product-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            showNotification('Selecciona al menos un producto', 'warning');
            return;
        }
        
        var fields = [];
        $('input[name="fields[]"]:checked').each(function() {
            fields.push($(this).val());
        });
        
        if (fields.length === 0) {
            showNotification('Selecciona al menos un campo a optimizar', 'warning');
            return;
        }

        if (!confirm('¿Optimizar ' + selectedIds.length + ' productos? Esta acción puede tardar varios minutos.')) {
            return;
        }
        
        bulkOptimize(selectedIds, fields);
    });
    
    $(document).on('click', '.btn-optimize-single', function() {
        var productId = $(this).data('id');
        optimizeSingleFromList(productId);
    });
    
    // FUNCIONES DE CARGA
    function loadProducts() {
        $('#table-loading').show();
        $('#table-container').css('opacity', '0.5');
        $('#select-all').prop('checked', false);

        $.ajax({
            url: aiOptAjaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                action: 'getProducts',
                page: currentPage,
                id_lang: $('#bulk-lang-select').val(),
                filter_search: $('#filter-search').val(),
                filter_active: $('#filter-active').val(),
                filter_manufacturer: $('#filter-manufacturer').val(),
                filter_category: $('#filter-category').val(),
                filter_optimized: $('#filter-optimized').val()
            },
            success: function(response) {
                $('#table-loading').hide();
                $('#table-container').css('opacity', '1');

                if (response.success) {
                    currentPage = response.data.pagination.current_page;
                    totalPages = response.data.pagination.total_pages;
                    totalProducts = response.data.pagination.total_products;
                    perPage = response.data.pagination.per_page;

                    renderProductsTable(response.data.products);
                    renderPagination();
                    updatePaginationInfo();
                    $('#total-products-badge').text(totalProducts + ' productos');
                } else {
                    showNotification(response.error || 'Error al cargar productos', 'error');
                }
            },
            error: function() {
                $('#table-loading').hide();
                $('#table-container').css('opacity', '1');
                showNotification('Error de conexión', 'error');
            }
        });
    }

    function renderProductsTable(products) {
        var tbody = $('#products-tbody');
        tbody.empty();

        if (products.length === 0) {
            tbody.append(
                '<tr id="no-products-row">' +
                '<td colspan="9" class="text-center">' +
                '<p class="text-muted" style="padding: 20px;">' +
                '<i class="icon-info-circle"></i> No se encontraron productos con los filtros seleccionados' +
                '</p></td></tr>'
            );
            return;
        }

        products.forEach(function(product) {
            var seoWarning = '';
            if (!product.meta_title || !product.meta_description) {
                seoWarning = ' <span class="label label-warning" title="Faltan meta datos SEO"><i class="icon-warning"></i> SEO</span>';
            }

            var optimizedBadge = '';
            if (product.is_optimized == 1) {
                var title = product.optimized_date ? 'Optimizado el ' + product.optimized_date : '';
                optimizedBadge = '<span class="label label-success optimized-badge" data-id="' + product.id_product + '" title="' + title + '"><i class="icon-check"></i> Sí</span>';
            } else {
                optimizedBadge = '<span class="label label-default optimized-badge" data-id="' + product.id_product + '"><i class="icon-times"></i> No</span>';
            }

            var row = '<tr data-id="' + product.id_product + '">' +
                '<td><input type="checkbox" class="product-checkbox" value="' + product.id_product + '"></td>' +
                '<td>' + product.id_product + '</td>' +
                '<td><span class="text-muted">' + (product.original_name ? truncate(escapeHtml(product.original_name), 60) : '-') + '</span></td>' +
                '<td><strong>' + escapeHtml(product.name || '') + '</strong>' + (product.is_optimized == 1 && product.original_name && product.original_name != product.name ? ' <span class="label label-success"><i class="icon-check"></i></span>' : '') + seoWarning + '</td>' +
                '<td><span class="text-muted">' + escapeHtml(product.manufacturer_name || '-') + '</span></td>' +
                '<td><span class="text-muted">' + truncate(escapeHtml(product.category_name || '-'), 25) + '</span></td>' +
                '<td class="text-center">' + (product.active == 1 ? '<span class="label label-success">Activo</span>' : '<span class="label label-danger">Inactivo</span>') + '</td>' +
                '<td class="text-center">' + optimizedBadge + '</td>' +
                '<td class="text-center">' +
                '<button type="button" class="btn btn-xs btn-primary btn-optimize-single" data-id="' + product.id_product + '" title="Optimizar producto"><i class="icon-magic"></i></button> ' +
                '<a href="' + getProductEditUrl(product.id_product) + '" class="btn btn-xs btn-default" target="_blank" title="Editar producto"><i class="icon-pencil"></i></a>' +
                '</td></tr>';

            tbody.append(row);
        });
    }

    function renderPagination() {
        var pagination = $('#pagination');
        pagination.empty();

        if (totalPages <= 1) {
            return;
        }

        pagination.append('<li class="' + (currentPage == 1 ? 'disabled' : '') + '"><a href="#" data-page="1" aria-label="Primera"><span aria-hidden="true">&laquo;&laquo;</span></a></li>');
        pagination.append('<li class="' + (currentPage == 1 ? 'disabled' : '') + '"><a href="#" data-page="' + (currentPage - 1) + '" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></a></li>');

        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
            pagination.append('<li class="disabled"><a href="#">...</a></li>');
        }

        for (var p = startPage; p <= endPage; p++) {
            pagination.append('<li class="' + (p == currentPage ? 'active' : '') + '"><a href="#" data-page="' + p + '">' + p + '</a></li>');
        }

        if (endPage < totalPages) {
            pagination.append('<li class="disabled"><a href="#">...</a></li>');
        }

        pagination.append('<li class="' + (currentPage == totalPages ? 'disabled' : '') + '"><a href="#" data-page="' + (currentPage + 1) + '" aria-label="Siguiente"><span aria-hidden="true">&raquo;</span></a></li>');
        pagination.append('<li class="' + (currentPage == totalPages ? 'disabled' : '') + '"><a href="#" data-page="' + totalPages + '" aria-label="Última"><span aria-hidden="true">&raquo;&raquo;</span></a></li>');
    }

    function updatePaginationInfo() {
        var from = totalProducts > 0 ? ((currentPage - 1) * perPage) + 1 : 0;
        var to = Math.min(currentPage * perPage, totalProducts);

        $('#showing-from').text(from);
        $('#showing-to').text(to);
        $('#showing-total').text(totalProducts);
    }

    // FUNCIONES DE OPTIMIZACIÓN
    function optimizeProduct() {
        showLoading(true);
        
        $.ajax({
            url: aiOptAjaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                action: 'optimize',
                id_product: aiOptProductId,
                id_lang: aiOptLangId
            },
            success: function(response) {
                showLoading(false);
                
                if (response.success) {
                    displayResults(response.data);
                    showNotification('Contenido optimizado generado correctamente', 'success');
                } else {
                    showNotification(response.error || 'Error al optimizar', 'error');
                }
            },
            error: function(xhr, status, error) {
                showLoading(false);
                showNotification('Error de conexión: ' + error, 'error');
            }
        });
    }
    
    function loadOriginalData() {
        $.ajax({
            url: aiOptAjaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                action: 'preview',
                id_product: aiOptProductId,
                id_lang: aiOptLangId
            },
            success: function(response) {
                if (response.success) {
                    $('#ai-original-title').val(response.data.name);
                    $('#ai-original-short').val(stripHtml(response.data.description_short));
                    $('#ai-original-description').val(stripHtml(response.data.description));
                    $('#ai-original-meta-title').val(response.data.meta_title);
                    $('#ai-original-meta-desc').val(response.data.meta_description);
                }
            }
        });
    }
    
    function regenerateField(field) {
        var btn = $('.btn-regenerate[data-field="' + field + '"]');
        btn.prop('disabled', true).find('i').addClass('icon-spin');
        
        $.ajax({
            url: aiOptAjaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                action: 'optimizeField',
                id_product: aiOptProductId,
                id_lang: aiOptLangId,
                field: field
            },
            success: function(response) {
                btn.prop('disabled', false).find('i').removeClass('icon-spin');
                
                if (response.success) {
                    setFieldValue(field, response.value);
                    showNotification('Campo regenerado', 'success');
                } else {
                    showNotification(response.error || 'Error al regenerar', 'error');
                }
            },
            error: function() {
                btn.prop('disabled', false).find('i').removeClass('icon-spin');
                showNotification('Error de conexión', 'error');
            }
        });
    }
    
    function applyChanges(data) {
        if (Object.keys(data).length === 0) {
            showNotification('No hay datos para aplicar', 'warning');
            return;
        }
        
        $('#btn-apply-all, #btn-apply-selected').prop('disabled', true);
        
        $.ajax({
            url: aiOptAjaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                action: 'apply',
                id_product: aiOptProductId,
                id_lang: aiOptLangId,
                data: JSON.stringify(data)
            },
            success: function(response) {
                $('#btn-apply-all, #btn-apply-selected').prop('disabled', false);
                
                if (response.success) {
                    showNotification('Producto actualizado correctamente', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.error || 'Error al guardar', 'error');
                }
            },
            error: function() {
                $('#btn-apply-all, #btn-apply-selected').prop('disabled', false);
                showNotification('Error de conexión', 'error');
            }
        });
    }
    
    function displayResults(data) {
        $('#ai-original-title').val(data.original.name);
        $('#ai-original-short').val(stripHtml(data.original.description_short));
        $('#ai-original-description').val(stripHtml(data.original.description));
        $('#ai-original-meta-title').val(data.original.meta_title);
        $('#ai-original-meta-desc').val(data.original.meta_description);
        
        $('#ai-optimized-title').val(data.optimized.title);
        $('#ai-optimized-short').val(data.optimized.short_description);
        $('#ai-optimized-description').val(data.optimized.description);
        $('#ai-optimized-meta-title').val(data.optimized.meta_title);
        $('#ai-optimized-meta-desc').val(data.optimized.meta_description);
        
        if (data.optimized.suggested_keywords && data.optimized.suggested_keywords.length > 0) {
            var keywordsHtml = '';
            data.optimized.suggested_keywords.forEach(function(keyword) {
                keywordsHtml += '<span class="label label-info mr-1">' + keyword + '</span> ';
            });
            $('#ai-suggested-keywords').html(keywordsHtml);
            $('#ai-keywords-group').show();
        }
        
        $('.ai-optimized-field').each(function() {
            updateCharCount($(this));
        });
        
        $('#ai-results').show();
    }
    
    function setFieldValue(field, value) {
        var fieldMap = {
            'title': '#ai-optimized-title',
            'short_description': '#ai-optimized-short',
            'description': '#ai-optimized-description',
            'meta_title': '#ai-optimized-meta-title',
            'meta_description': '#ai-optimized-meta-desc'
        };
        
        if (fieldMap[field]) {
            $(fieldMap[field]).val(value);
            updateCharCount($(fieldMap[field]));
        }
    }
    
    function getAllOptimizedData() {
        return {
            title: $('#ai-optimized-title').val(),
            short_description: $('#ai-optimized-short').val(),
            description: $('#ai-optimized-description').val(),
            meta_title: $('#ai-optimized-meta-title').val(),
            meta_description: $('#ai-optimized-meta-desc').val()
        };
    }
    
    function getModifiedData() {
        var data = {};
        var optimized = getAllOptimizedData();
        
        for (var key in optimized) {
            if (optimized[key] && optimized[key].trim() !== '') {
                data[key] = optimized[key];
            }
        }
        
        return data;
    }
    
    function bulkOptimize(productIds, fields) {
        var total = productIds.length;
        var current = 0;
        var results = {success: [], errors: []};
        
        $('#bulk-progress').show();
        $('#btn-bulk-optimize').prop('disabled', true);
        updateProgress(0, total);
        
        function processNext() {
            if (current >= total) {
                $('#bulk-status').html('<span class="text-success"><i class="icon-check"></i> Optimización completada: ' + results.success.length + ' exitosos, ' + results.errors.length + ' errores</span>');
                $('#btn-bulk-optimize').prop('disabled', false);
                
                setTimeout(function() {
                    loadProducts();
                    $('#bulk-progress').fadeOut();
                }, 2000);
                return;
            }
            
            var productId = productIds[current];
            var productName = $('tr[data-id="' + productId + '"] td:nth-child(3) strong').text() || 'ID ' + productId;
            $('#bulk-status').html('<i class="icon-spinner icon-spin"></i> Procesando: <strong>' + productName + '</strong> (' + (current + 1) + ' de ' + total + ')');
            
            $.ajax({
                url: aiOptAjaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    ajax: 1,
                    action: 'optimize',
                    id_product: productId,
                    id_lang: $('#bulk-lang-select').val()
                },
                success: function(response) {
                    if (response.success) {
                        applyBulkChanges(productId, response.data.optimized, fields, function(success) {
                            if (success) {
                                results.success.push(productId);
                                updateOptimizedBadge(productId, true);
                            } else {
                                results.errors.push(productId);
                            }
                            current++;
                            updateProgress(current, total);
                            processNext();
                        });
                    } else {
                        results.errors.push(productId);
                        current++;
                        updateProgress(current, total);
                        processNext();
                    }
                },
                error: function() {
                    results.errors.push(productId);
                    current++;
                    updateProgress(current, total);
                    processNext();
                }
            });
        }
        
        processNext();
    }
    
    function applyBulkChanges(productId, optimized, fields, callback) {
        var data = {};
        fields.forEach(function(field) {
            if (optimized[field]) {
                data[field] = optimized[field];
            }
        });
        
        $.ajax({
            url: aiOptAjaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                action: 'apply',
                id_product: productId,
                id_lang: $('#bulk-lang-select').val(),
                data: JSON.stringify(data)
            },
            success: function(response) {
                callback(response.success);
            },
            error: function() {
                callback(false);
            }
        });
    }

    function updateOptimizedBadge(productId, isOptimized) {
        var badge = $('.optimized-badge[data-id="' + productId + '"]');
        if (isOptimized) {
            badge.removeClass('label-default').addClass('label-success')
                .html('<i class="icon-check"></i> Sí');
        } else {
            badge.removeClass('label-success').addClass('label-default')
                .html('<i class="icon-times"></i> No');
        }
    }
    
    function optimizeSingleFromList(productId) {
        var btn = $('.btn-optimize-single[data-id="' + productId + '"]');
        var originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="icon-spinner icon-spin"></i>');
        
        $.ajax({
            url: aiOptAjaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                action: 'optimize',
                id_product: productId,
                id_lang: $('#bulk-lang-select').val()
            },
            success: function(response) {
                btn.prop('disabled', false).html(originalHtml);
                
                if (response.success) {
                    showPreviewModal(productId, response.data);
                } else {
                    showNotification(response.error || 'Error al optimizar', 'error');
                }
            },
            error: function() {
                btn.prop('disabled', false).html(originalHtml);
                showNotification('Error de conexión', 'error');
            }
        });
    }
    
    function showPreviewModal(productId, data) {
        $('#preview-product-name').text(data.original.name || 'Producto ' + productId);
        
        var html = '<div class="table-responsive"><table class="table table-bordered">';
        
        var fields = [
            {key: 'title', label: 'Título', original: data.original.name, optimized: data.optimized.title},
            {key: 'short_description', label: 'Resumen', original: stripHtml(data.original.description_short), optimized: data.optimized.short_description},
            {key: 'meta_title', label: 'Meta Título', original: data.original.meta_title, optimized: data.optimized.meta_title},
            {key: 'meta_description', label: 'Meta Descripción', original: data.original.meta_description, optimized: data.optimized.meta_description}
        ];

        html += '<thead><tr><th width="15%">Campo</th><th width="42%">Original</th><th width="43%">Optimizado</th></tr></thead><tbody>';
        
        fields.forEach(function(field) {
            html += '<tr>';
            html += '<td><strong>' + field.label + '</strong></td>';
            html += '<td class="text-muted">' + escapeHtml(field.original || '-') + '</td>';
            html += '<td class="text-success">' + escapeHtml(field.optimized || '-') + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table></div>';

        if (data.optimized.suggested_keywords && data.optimized.suggested_keywords.length > 0) {
            html += '<div class="alert alert-info"><strong>Keywords sugeridas:</strong> ';
            data.optimized.suggested_keywords.forEach(function(kw) {
                html += '<span class="label label-default">' + escapeHtml(kw) + '</span> ';
            });
            html += '</div>';
        }
        
        $('#preview-content').html(html);
        $('#preview-modal').data('productId', productId).data('optimized', data.optimized).modal('show');
    }
    
    $('#btn-apply-preview').on('click', function() {
        var productId = $('#preview-modal').data('productId');
        var optimized = $('#preview-modal').data('optimized');
        
        $(this).prop('disabled', true).html('<i class="icon-spinner icon-spin"></i> Guardando...');
        
        $.ajax({
            url: aiOptAjaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                action: 'apply',
                id_product: productId,
                id_lang: $('#bulk-lang-select').val(),
                data: JSON.stringify(optimized)
            },
            success: function(response) {
                $('#btn-apply-preview').prop('disabled', false).html('<i class="icon-check"></i> Aplicar Cambios');
                $('#preview-modal').modal('hide');
                
                if (response.success) {
                    showNotification('Producto actualizado correctamente', 'success');
                    updateOptimizedBadge(productId, true);
                    var row = $('tr[data-id="' + productId + '"]');
                    row.find('.label-warning').remove();
                } else {
                    showNotification(response.error || 'Error al guardar', 'error');
                }
            },
            error: function() {
                $('#btn-apply-preview').prop('disabled', false).html('<i class="icon-check"></i> Aplicar Cambios');
                showNotification('Error de conexión', 'error');
            }
        });
    });
    
    // UTILIDADES
    function showLoading(show) {
        if (show) {
            $('#ai-loading').show();
            $('#ai-results').hide();
            $('#btn-optimize-all').prop('disabled', true);
        } else {
            $('#ai-loading').hide();
            $('#btn-optimize-all').prop('disabled', false);
        }
    }
    
    function updateCharCount(element) {
        var count = element.val().length;
        var counter = element.siblings('.char-count');
        
        if (counter.length) {
            counter.find('.current').text(count);
            
            var max = element.attr('maxlength');
            if (max) {
                if (count > max * 0.9) {
                    counter.addClass('text-warning');
                } else {
                    counter.removeClass('text-warning');
                }
                if (count > max) {
                    counter.addClass('text-danger').removeClass('text-warning');
                } else {
                    counter.removeClass('text-danger');
                }
            }
        }
    }
    
    function updateProgress(current, total) {
        var percent = Math.round((current / total) * 100);
        $('.progress-bar').css('width', percent + '%');
        $('.progress-text').text(percent + '%');
    }
    
    function stripHtml(html) {
        if (!html) return '';
        var tmp = document.createElement('DIV');
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function truncate(str, length) {
        if (!str) return '';
        return str.length > length ? str.substring(0, length) + '...' : str;
    }

    function getProductEditUrl(productId) {
        var baseUrl = aiOptAjaxUrl.replace('AdminAiProductOptimizer', 'AdminProducts');
        return baseUrl + '&id_product=' + productId + '&updateproduct=1';
    }
    
    function showNotification(message, type) {
        type = type || 'info';
        
        if (typeof showSuccessMessage === 'function' && type === 'success') {
            showSuccessMessage(message);
        } else if (typeof showErrorMessage === 'function' && type === 'error') {
            showErrorMessage(message);
        } else {
            var alertClass = 'alert-info';
            if (type === 'success') alertClass = 'alert-success';
            if (type === 'error') alertClass = 'alert-danger';
            if (type === 'warning') alertClass = 'alert-warning';

            var notification = $('<div class="alert ' + alertClass + ' alert-dismissible" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">' +
                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                message + '</div>');

            $('body').append(notification);
            
            setTimeout(function() {
                notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 4000);
        }
    }
});
