<?php
/**
 * Clase auxiliar para optimización de productos
 * 
 * @author    OKVIKI <https://okviki.com>
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductOptimizer
{
    private $module;
    private $provider;

    public function __construct($module)
    {
        $this->module = $module;
        $this->provider = $module->getAIProvider();
    }

    public function optimize($idProduct, $idLang = null)
    {
        if (!$idLang) {
            $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        }

        $product = new Product($idProduct, false, $idLang);

        if (!Validate::isLoadedObject($product)) {
            throw new Exception('Producto no encontrado');
        }

        $category = new Category($product->id_category_default, $idLang);
        $categoryName = $category->name ?? 'Sin categoría';

        $manufacturer = '';
        if ($product->id_manufacturer) {
            $manu = new Manufacturer($product->id_manufacturer, $idLang);
            $manufacturer = $manu->name ?? '';
        }

        $options = [
            'language' => Configuration::get('AIOPT_LANGUAGE'),
            'tone' => Configuration::get('AIOPT_TONE'),
            'brand' => $manufacturer,
            'features' => $this->getProductFeatures($idProduct, $idLang),
        ];

        $result = $this->provider->optimizeProduct(
            $product->name,
            $product->description,
            $categoryName,
            $options
        );

        return [
            'original' => [
                'name' => $product->name,
                'description_short' => $product->description_short,
                'description' => $product->description,
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
            ],
            'optimized' => $result,
        ];
    }

    public function optimizeField($idProduct, $field, $idLang = null)
    {
        if (!$idLang) {
            $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        }

        $product = new Product($idProduct, false, $idLang);

        if (!Validate::isLoadedObject($product)) {
            throw new Exception('Producto no encontrado');
        }

        $category = new Category($product->id_category_default, $idLang);
        $categoryName = $category->name ?? 'Sin categoría';

        $options = [
            'language' => Configuration::get('AIOPT_LANGUAGE'),
            'tone' => Configuration::get('AIOPT_TONE'),
        ];

        switch ($field) {
            case 'title':
                return $this->provider->generateTitle($product->name, $categoryName, $options);

            case 'short_description':
                return $this->provider->generateShortDescription(
                    $product->name,
                    $product->description,
                    $options
                );

            case 'description':
                return $this->provider->generateLongDescription(
                    $product->name,
                    $product->description,
                    $categoryName,
                    $options
                );

            case 'meta_title':
                return $this->provider->generateMetaTitle($product->name, $categoryName, $options);

            case 'meta_description':
                return $this->provider->generateMetaDescription(
                    $product->name,
                    $product->description_short ?: $product->description,
                    $options
                );

            default:
                throw new Exception('Campo no válido: ' . $field);
        }
    }

    public function applyOptimizations($idProduct, $data, $idLang = null)
    {
        if (!$idLang) {
            $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        }

        $product = new Product($idProduct);

        if (!Validate::isLoadedObject($product)) {
            throw new Exception('Producto no encontrado');
        }

        if (isset($data['title']) && !empty($data['title'])) {
            $product->name[$idLang] = pSQL($data['title']);
            $product->link_rewrite[$idLang] = Tools::str2url($data['title']);
        }

        if (isset($data['short_description']) && !empty($data['short_description'])) {
            $product->description_short[$idLang] = $data['short_description'];
        }

        if (isset($data['description']) && !empty($data['description'])) {
            $product->description[$idLang] = $data['description'];
        }

        if (isset($data['meta_title']) && !empty($data['meta_title'])) {
            $product->meta_title[$idLang] = pSQL($data['meta_title']);
        }

        if (isset($data['meta_description']) && !empty($data['meta_description'])) {
            $product->meta_description[$idLang] = pSQL($data['meta_description']);
        }

        if (!$product->update()) {
            throw new Exception('Error al guardar el producto');
        }

        return true;
    }

    private function getProductFeatures($idProduct, $idLang)
    {
        $features = Product::getFrontFeaturesStatic($idLang, $idProduct);
        
        if (empty($features)) {
            return '';
        }

        $featureList = [];
        foreach ($features as $feature) {
            $featureList[] = $feature['name'] . ': ' . $feature['value'];
        }

        return implode(', ', $featureList);
    }

    public function bulkOptimize($productIds, $idLang = null, $fieldsToOptimize = null)
    {
        $results = [
            'success' => [],
            'errors' => [],
        ];

        foreach ($productIds as $idProduct) {
            try {
                $optimized = $this->optimize($idProduct, $idLang);
                
                if ($fieldsToOptimize) {
                    $filtered = [];
                    foreach ($fieldsToOptimize as $field) {
                        if (isset($optimized['optimized'][$field])) {
                            $filtered[$field] = $optimized['optimized'][$field];
                        }
                    }
                    $optimized['optimized'] = $filtered;
                }

                $results['success'][$idProduct] = $optimized;
            } catch (Exception $e) {
                $results['errors'][$idProduct] = $e->getMessage();
            }
        }

        return $results;
    }
}
