<?php
/**
 * Controlador admin para AI Product Optimizer
 * Compatible con PrestaShop 1.7.x y 8.x
 * Compatible con multitienda y tienda única
 * 
 * @author    OKVIKI <https://okviki.com>
 * @version 1.2.0
 */

class AdminAiProductOptimizerController extends ModuleAdminController
{
    protected $perPage = 500;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        parent::__construct();
    }

    public function init()
    {
        parent::init();
    }

    /**
     * Obtener el ID de la tienda actual
     */
    protected function getShopId()
    {
        if (Shop::isFeatureActive()) {
            return (int) $this->context->shop->id;
        }
        return 1;
    }

    /**
     * Manejar peticiones AJAX
     */
    public function initContent()
    {
        parent::initContent();

        if (Tools::getValue('ajax')) {
            $this->handleAjaxRequest();
            exit;
        }

        $this->renderProductList();
    }

    /**
     * Manejar peticiones AJAX
     */
    protected function handleAjaxRequest()
    {
        header('Content-Type: application/json');

        try {
            $action = Tools::getValue('action');

            switch ($action) {
                case 'optimize':
                    $this->ajaxOptimize();
                    break;

                case 'optimizeField':
                    $this->ajaxOptimizeField();
                    break;

                case 'apply':
                    $this->ajaxApply();
                    break;

                case 'preview':
                    $this->ajaxPreview();
                    break;

                case 'bulkOptimize':
                    $this->ajaxBulkOptimize();
                    break;

                case 'getProducts':
                    $this->ajaxGetProducts();
                    break;

                case 'markOptimized':
                    $this->ajaxMarkOptimized();
                    break;

                default:
                    throw new Exception('Acción no válida');
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Optimizar producto completo
     */
    protected function ajaxOptimize()
    {
        $idProduct = (int) Tools::getValue('id_product');
        $idLang = (int) Tools::getValue('id_lang', Configuration::get('PS_LANG_DEFAULT'));

        if (!$idProduct) {
            throw new Exception('ID de producto no válido');
        }

        $optimizer = new ProductOptimizer($this->module);
        $result = $optimizer->optimize($idProduct, $idLang);

        echo json_encode([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Optimizar un campo específico
     */
    protected function ajaxOptimizeField()
    {
        $idProduct = (int) Tools::getValue('id_product');
        $field = Tools::getValue('field');
        $idLang = (int) Tools::getValue('id_lang', Configuration::get('PS_LANG_DEFAULT'));

        if (!$idProduct) {
            throw new Exception('ID de producto no válido');
        }

        $validFields = ['title', 'short_description', 'description', 'meta_title', 'meta_description'];
        if (!in_array($field, $validFields)) {
            throw new Exception('Campo no válido');
        }

        $optimizer = new ProductOptimizer($this->module);
        $result = $optimizer->optimizeField($idProduct, $field, $idLang);

        echo json_encode([
            'success' => true,
            'field' => $field,
            'value' => $result,
        ]);
    }

    /**
     * Aplicar optimizaciones al producto
     */
    protected function ajaxApply()
    {
        $idProduct = (int) Tools::getValue('id_product');
        $idLang = (int) Tools::getValue('id_lang', Configuration::get('PS_LANG_DEFAULT'));
        $data = Tools::getValue('data');

        if (!$idProduct) {
            throw new Exception('ID de producto no válido');
        }

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (!$data || !is_array($data)) {
            throw new Exception('Datos de optimización no válidos');
        }

        $product = new Product($idProduct, false, $idLang);
        $originalName = $product->name;

        $optimizer = new ProductOptimizer($this->module);
        $optimizer->applyOptimizations($idProduct, $data, $idLang);

        $this->setProductOptimized($idProduct, $idLang, $originalName);

        echo json_encode([
            'success' => true,
            'message' => 'Producto actualizado correctamente',
        ]);
    }

    /**
     * Previsualizar contenido original del producto
     */
    protected function ajaxPreview()
    {
        $idProduct = (int) Tools::getValue('id_product');
        $idLang = (int) Tools::getValue('id_lang', Configuration::get('PS_LANG_DEFAULT'));

        if (!$idProduct) {
            throw new Exception('ID de producto no válido');
        }

        $product = new Product($idProduct, false, $idLang);

        if (!Validate::isLoadedObject($product)) {
            throw new Exception('Producto no encontrado');
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'name' => $product->name,
                'description_short' => $product->description_short,
                'description' => $product->description,
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
            ],
        ]);
    }

    /**
     * Optimización masiva
     */
    protected function ajaxBulkOptimize()
    {
        $productIds = Tools::getValue('product_ids');
        $idLang = (int) Tools::getValue('id_lang', Configuration::get('PS_LANG_DEFAULT'));
        $fields = Tools::getValue('fields');

        if (is_string($productIds)) {
            $productIds = json_decode($productIds, true);
        }

        if (is_string($fields)) {
            $fields = json_decode($fields, true);
        }

        if (!$productIds || !is_array($productIds)) {
            throw new Exception('IDs de producto no válidos');
        }

        $optimizer = new ProductOptimizer($this->module);
        $results = $optimizer->bulkOptimize($productIds, $idLang, $fields);

        echo json_encode([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * AJAX: Obtener productos con filtros y paginación
     */
    protected function ajaxGetProducts()
    {
        $page = (int) Tools::getValue('page', 1);
        $idLang = (int) Tools::getValue('id_lang', Configuration::get('PS_LANG_DEFAULT'));
        $filters = [
            'active' => Tools::getValue('filter_active', ''),
            'id_manufacturer' => (int) Tools::getValue('filter_manufacturer', 0),
            'id_category' => (int) Tools::getValue('filter_category', 0),
            'optimized' => Tools::getValue('filter_optimized', ''),
            'search' => Tools::getValue('filter_search', ''),
        ];

        $products = $this->getProductsForList($page, $idLang, $filters);
        $totalProducts = $this->getTotalProducts($idLang, $filters);
        $totalPages = ceil($totalProducts / $this->perPage);

        echo json_encode([
            'success' => true,
            'data' => [
                'products' => $products,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_products' => $totalProducts,
                    'per_page' => $this->perPage,
                ],
            ],
        ]);
    }

    /**
     * AJAX: Marcar producto como optimizado
     */
    protected function ajaxMarkOptimized()
    {
        $idProduct = (int) Tools::getValue('id_product');
        $idLang = (int) Tools::getValue('id_lang', Configuration::get('PS_LANG_DEFAULT'));
        $optimized = (int) Tools::getValue('optimized', 1);

        if (!$idProduct) {
            throw new Exception('ID de producto no válido');
        }

        if ($optimized) {
            $product = new Product($idProduct, false, $idLang);
            $this->setProductOptimized($idProduct, $idLang, $product->name);
        } else {
            $this->setProductNotOptimized($idProduct, $idLang);
        }

        echo json_encode([
            'success' => true,
        ]);
    }

    /**
     * Renderizar lista de productos
     */
    protected function renderProductList()
    {
        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $page = (int) Tools::getValue('page', 1);
        
        $filters = [
            'active' => Tools::getValue('filter_active', ''),
            'id_manufacturer' => (int) Tools::getValue('filter_manufacturer', 0),
            'id_category' => (int) Tools::getValue('filter_category', 0),
            'optimized' => Tools::getValue('filter_optimized', ''),
            'search' => Tools::getValue('filter_search', ''),
        ];

        $totalProducts = $this->getTotalProducts($idLang, $filters);
        $totalPages = ceil($totalProducts / $this->perPage);

        $this->context->smarty->assign([
            'products' => $this->getProductsForList($page, $idLang, $filters),
            'languages' => Language::getLanguages(true),
            'default_lang' => $idLang,
            'ajax_url' => $this->context->link->getAdminLink('AdminAiProductOptimizer'),
            'provider' => Configuration::get('AIOPT_PROVIDER'),
            'manufacturers' => Manufacturer::getManufacturers(false, $idLang, true),
            'categories' => $this->getCategoriesForFilter($idLang),
            'filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_products' => $totalProducts,
                'per_page' => $this->perPage,
            ],
        ]);

        $this->setTemplate('list.tpl');
    }

    /**
     * Obtener productos para la lista con filtros y paginación
     */
    protected function getProductsForList($page = 1, $idLang = null, $filters = [])
    {
        if (!$idLang) {
            $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        }

        $idShop = $this->getShopId();
        $offset = ($page - 1) * $this->perPage;

        $sql = new DbQuery();
        $sql->select('p.id_product, pl.name, pl.description_short, pl.meta_title, pl.meta_description, ps.active, p.id_manufacturer');
        $sql->select('m.name as manufacturer_name');
        $sql->select('cl.name as category_name');
        $sql->select('IFNULL(ao.optimized, 0) as is_optimized, ao.optimized_date, ao.original_name');
        $sql->from('product', 'p');
        $sql->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . (int) $idShop);
        $sql->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . (int) $idLang . ' AND pl.id_shop = ' . (int) $idShop);
        $sql->leftJoin('manufacturer', 'm', 'p.id_manufacturer = m.id_manufacturer');
        $sql->leftJoin('category_lang', 'cl', 'p.id_category_default = cl.id_category AND cl.id_lang = ' . (int) $idLang . ' AND cl.id_shop = ' . (int) $idShop);
        $sql->leftJoin('aiopt_optimized', 'ao', 'p.id_product = ao.id_product AND ao.id_lang = ' . (int) $idLang);

        $this->applyFilters($sql, $filters, $idLang);

        $sql->orderBy('p.id_product DESC');
        $sql->limit($this->perPage, $offset);

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Obtener total de productos con filtros
     */
    protected function getTotalProducts($idLang, $filters = [])
    {
        $idShop = $this->getShopId();

        $sql = new DbQuery();
        $sql->select('COUNT(DISTINCT p.id_product)');
        $sql->from('product', 'p');
        $sql->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . (int) $idShop);
        $sql->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . (int) $idLang . ' AND pl.id_shop = ' . (int) $idShop);
        $sql->leftJoin('aiopt_optimized', 'ao', 'p.id_product = ao.id_product AND ao.id_lang = ' . (int) $idLang);

        $this->applyFilters($sql, $filters, $idLang);

        return (int) Db::getInstance()->getValue($sql);
    }

    /**
     * Aplicar filtros a la consulta
     */
    protected function applyFilters($sql, $filters, $idLang)
    {
        if ($filters['active'] !== '' && $filters['active'] !== null) {
            $sql->where('ps.active = ' . (int) $filters['active']);
        }

        if (!empty($filters['id_manufacturer'])) {
            $sql->where('p.id_manufacturer = ' . (int) $filters['id_manufacturer']);
        }

        if (!empty($filters['id_category'])) {
            $sql->leftJoin('category_product', 'cp', 'p.id_product = cp.id_product');
            $sql->where('(p.id_category_default = ' . (int) $filters['id_category'] . ' OR cp.id_category = ' . (int) $filters['id_category'] . ')');
        }

        if ($filters['optimized'] !== '' && $filters['optimized'] !== null) {
            if ($filters['optimized'] == '1') {
                $sql->where('ao.optimized = 1');
            } else {
                $sql->where('(ao.optimized IS NULL OR ao.optimized = 0)');
            }
        }

        if (!empty($filters['search'])) {
            $search = pSQL($filters['search']);
            $sql->where('(pl.name LIKE "%' . $search . '%" OR p.id_product = "' . (int) $filters['search'] . '" OR p.reference LIKE "%' . $search . '%")');
        }
    }

    /**
     * Obtener categorías para el filtro
     */
    protected function getCategoriesForFilter($idLang)
    {
        $idShop = $this->getShopId();

        $sql = 'SELECT c.id_category, c.level_depth, cl.name 
                FROM `' . _DB_PREFIX_ . 'category` c
                LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON c.id_category = cl.id_category AND cl.id_lang = ' . (int) $idLang . ' AND cl.id_shop = ' . (int) $idShop . '
                WHERE c.active = 1
                ORDER BY c.level_depth, cl.name';
        
        $categories = Db::getInstance()->executeS($sql);
        $result = [];
        
        if ($categories) {
            foreach ($categories as $cat) {
                $depth = max(0, (int) $cat['level_depth'] - 1);
                $result[] = [
                    'id_category' => $cat['id_category'],
                    'name' => str_repeat('— ', $depth) . $cat['name'],
                ];
            }
        }
        
        return $result;
    }

    /**
     * Marcar producto como optimizado
     */
    protected function setProductOptimized($idProduct, $idLang, $originalName = null)
    {
        $this->ensureOptimizedTableExists();

        $exists = Db::getInstance()->getValue(
            'SELECT id_product FROM `' . _DB_PREFIX_ . 'aiopt_optimized` 
            WHERE id_product = ' . (int) $idProduct . ' AND id_lang = ' . (int) $idLang
        );

        if ($exists) {
            return Db::getInstance()->update('aiopt_optimized', [
                'optimized' => 1,
                'optimized_date' => date('Y-m-d H:i:s'),
                'original_name' => $originalName ? pSQL($originalName) : null,
            ], 'id_product = ' . (int) $idProduct . ' AND id_lang = ' . (int) $idLang);
        } else {
            return Db::getInstance()->insert('aiopt_optimized', [
                'id_product' => (int) $idProduct,
                'id_lang' => (int) $idLang,
                'optimized' => 1,
                'optimized_date' => date('Y-m-d H:i:s'),
                'original_name' => $originalName ? pSQL($originalName) : null,
            ]);
        }
    }

    /**
     * Marcar producto como no optimizado
     */
    protected function setProductNotOptimized($idProduct, $idLang)
    {
        return Db::getInstance()->update('aiopt_optimized', [
            'optimized' => 0,
            'optimized_date' => null,
        ], 'id_product = ' . (int) $idProduct . ' AND id_lang = ' . (int) $idLang);
    }

    /**
     * Asegurar que la tabla de optimizados existe
     */
    protected function ensureOptimizedTableExists()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aiopt_optimized` (
            `id_product` INT(10) UNSIGNED NOT NULL,
            `id_lang` INT(10) UNSIGNED NOT NULL,
            `optimized` TINYINT(1) NOT NULL DEFAULT 0,
            `optimized_date` DATETIME DEFAULT NULL,
            `original_name` VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (`id_product`, `id_lang`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        return Db::getInstance()->execute($sql);
    }
}
