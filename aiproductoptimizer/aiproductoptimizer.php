<?php
/**
 * AI Product Optimizer - Módulo PrestaShop
 * Optimiza títulos, descripciones y meta tags de productos usando IA
 * 
 * Compatible con PrestaShop 1.7.x y 8.x
 * Compatible con multitienda y tienda única
 * 
 * @author    OKVIKI <https://okviki.com>
 * @version 1.1.0
 * @license MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/classes/AIProvider.php';
require_once dirname(__FILE__) . '/classes/OpenAIProvider.php';
require_once dirname(__FILE__) . '/classes/GeminiProvider.php';
require_once dirname(__FILE__) . '/classes/ProductOptimizer.php';

class AiProductOptimizer extends Module
{
    public function __construct()
    {
        $this->name = 'aiproductoptimizer';
        $this->tab = 'administration';
        $this->version = '1.2.0';
        $this->author = 'OKVIKI';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('OKVIKI AI Product Optimizer');
        $this->description = $this->l('Optimiza automáticamente títulos, descripciones y meta tags de productos usando ChatGPT o Gemini.');
        $this->confirmUninstall = $this->l('¿Estás seguro de que quieres desinstalar este módulo?');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => '8.99.99'];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->installTab()
            && $this->installDatabase()
            && Configuration::updateValue('AIOPT_PROVIDER', 'openai')
            && Configuration::updateValue('AIOPT_OPENAI_KEY', '')
            && Configuration::updateValue('AIOPT_GEMINI_KEY', '')
            && Configuration::updateValue('AIOPT_OPENAI_MODEL', 'gpt-4o-mini')
            && Configuration::updateValue('AIOPT_GEMINI_MODEL', 'gemini-1.5-flash')
            && Configuration::updateValue('AIOPT_LANGUAGE', 'es')
            && Configuration::updateValue('AIOPT_TONE', 'profesional')
            && Configuration::updateValue('AIOPT_INCLUDE_KEYWORDS', 1)
            && Configuration::updateValue('AIOPT_AUTO_SAVE', 0);
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallTab()
            && $this->uninstallDatabase()
            && Configuration::deleteByName('AIOPT_PROVIDER')
            && Configuration::deleteByName('AIOPT_OPENAI_KEY')
            && Configuration::deleteByName('AIOPT_GEMINI_KEY')
            && Configuration::deleteByName('AIOPT_OPENAI_MODEL')
            && Configuration::deleteByName('AIOPT_GEMINI_MODEL')
            && Configuration::deleteByName('AIOPT_LANGUAGE')
            && Configuration::deleteByName('AIOPT_TONE')
            && Configuration::deleteByName('AIOPT_INCLUDE_KEYWORDS')
            && Configuration::deleteByName('AIOPT_AUTO_SAVE');
    }

    /**
     * Crear tabla para tracking de productos optimizados
     */
    private function installDatabase()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aiopt_optimized` (
            `id_product` INT(10) UNSIGNED NOT NULL,
            `id_lang` INT(10) UNSIGNED NOT NULL,
            `optimized` TINYINT(1) NOT NULL DEFAULT 0,
            `optimized_date` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id_product`, `id_lang`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Eliminar tabla
     */
    private function uninstallDatabase()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'aiopt_optimized`');
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminAiProductOptimizer';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'AI Product Optimizer';
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->module = $this->name;
        return $tab->add();
    }

    private function uninstallTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminAiProductOptimizer');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitAiProductOptimizer')) {
            $provider = Tools::getValue('AIOPT_PROVIDER');
            $openai_key = Tools::getValue('AIOPT_OPENAI_KEY');
            $gemini_key = Tools::getValue('AIOPT_GEMINI_KEY');
            $openai_model = Tools::getValue('AIOPT_OPENAI_MODEL');
            $gemini_model = Tools::getValue('AIOPT_GEMINI_MODEL');
            $language = Tools::getValue('AIOPT_LANGUAGE');
            $tone = Tools::getValue('AIOPT_TONE');
            $include_keywords = Tools::getValue('AIOPT_INCLUDE_KEYWORDS');
            $auto_save = Tools::getValue('AIOPT_AUTO_SAVE');

            Configuration::updateValue('AIOPT_PROVIDER', $provider);
            Configuration::updateValue('AIOPT_OPENAI_KEY', $openai_key);
            Configuration::updateValue('AIOPT_GEMINI_KEY', $gemini_key);
            Configuration::updateValue('AIOPT_OPENAI_MODEL', $openai_model);
            Configuration::updateValue('AIOPT_GEMINI_MODEL', $gemini_model);
            Configuration::updateValue('AIOPT_LANGUAGE', $language);
            Configuration::updateValue('AIOPT_TONE', $tone);
            Configuration::updateValue('AIOPT_INCLUDE_KEYWORDS', $include_keywords);
            Configuration::updateValue('AIOPT_AUTO_SAVE', $auto_save);

            $output .= $this->displayConfirmation($this->l('Configuración guardada correctamente.'));
        }

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAiProductOptimizer';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuración de AI Product Optimizer'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Proveedor de IA'),
                        'name' => 'AIOPT_PROVIDER',
                        'desc' => $this->l('Selecciona qué servicio de IA usar para optimizar los productos'),
                        'options' => [
                            'query' => [
                                ['id' => 'openai', 'name' => 'OpenAI (ChatGPT)'],
                                ['id' => 'gemini', 'name' => 'Google Gemini'],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('API Key de OpenAI'),
                        'name' => 'AIOPT_OPENAI_KEY',
                        'desc' => $this->l('Tu clave API de OpenAI (comienza con sk-)'),
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Modelo de OpenAI'),
                        'name' => 'AIOPT_OPENAI_MODEL',
                        'options' => [
                            'query' => [
                                ['id' => 'gpt-4o-mini', 'name' => 'GPT-4o Mini (Rápido y económico)'],
                                ['id' => 'gpt-4o', 'name' => 'GPT-4o (Mejor calidad)'],
                                ['id' => 'gpt-4-turbo', 'name' => 'GPT-4 Turbo'],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('API Key de Google Gemini'),
                        'name' => 'AIOPT_GEMINI_KEY',
                        'desc' => $this->l('Tu clave API de Google AI Studio'),
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Modelo de Gemini'),
                        'name' => 'AIOPT_GEMINI_MODEL',
                        'options' => [
                            'query' => [
                                ['id' => 'gemini-1.5-flash', 'name' => 'Gemini 1.5 Flash (Rápido)'],
                                ['id' => 'gemini-1.5-pro', 'name' => 'Gemini 1.5 Pro (Mejor calidad)'],
                                ['id' => 'gemini-2.0-flash-exp', 'name' => 'Gemini 2.0 Flash (Experimental)'],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Idioma de optimización'),
                        'name' => 'AIOPT_LANGUAGE',
                        'options' => [
                            'query' => [
                                ['id' => 'es', 'name' => 'Español'],
                                ['id' => 'en', 'name' => 'English'],
                                ['id' => 'fr', 'name' => 'Français'],
                                ['id' => 'de', 'name' => 'Deutsch'],
                                ['id' => 'it', 'name' => 'Italiano'],
                                ['id' => 'pt', 'name' => 'Português'],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Tono de escritura'),
                        'name' => 'AIOPT_TONE',
                        'desc' => $this->l('El estilo de escritura para las descripciones'),
                        'options' => [
                            'query' => [
                                ['id' => 'profesional', 'name' => 'Profesional'],
                                ['id' => 'casual', 'name' => 'Casual/Cercano'],
                                ['id' => 'persuasivo', 'name' => 'Persuasivo/Ventas'],
                                ['id' => 'tecnico', 'name' => 'Técnico/Detallado'],
                                ['id' => 'lujoso', 'name' => 'Lujoso/Premium'],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Incluir keywords sugeridas'),
                        'name' => 'AIOPT_INCLUDE_KEYWORDS',
                        'desc' => $this->l('La IA sugerirá keywords relevantes para el producto'),
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Sí')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Auto-guardar optimizaciones'),
                        'name' => 'AIOPT_AUTO_SAVE',
                        'desc' => $this->l('Guardar automáticamente sin previsualización (no recomendado)'),
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Sí')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Guardar'),
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        return [
            'AIOPT_PROVIDER' => Configuration::get('AIOPT_PROVIDER'),
            'AIOPT_OPENAI_KEY' => Configuration::get('AIOPT_OPENAI_KEY'),
            'AIOPT_GEMINI_KEY' => Configuration::get('AIOPT_GEMINI_KEY'),
            'AIOPT_OPENAI_MODEL' => Configuration::get('AIOPT_OPENAI_MODEL'),
            'AIOPT_GEMINI_MODEL' => Configuration::get('AIOPT_GEMINI_MODEL'),
            'AIOPT_LANGUAGE' => Configuration::get('AIOPT_LANGUAGE'),
            'AIOPT_TONE' => Configuration::get('AIOPT_TONE'),
            'AIOPT_INCLUDE_KEYWORDS' => Configuration::get('AIOPT_INCLUDE_KEYWORDS'),
            'AIOPT_AUTO_SAVE' => Configuration::get('AIOPT_AUTO_SAVE'),
        ];
    }

    public function hookActionAdminControllerSetMedia()
    {
        $controller = Tools::getValue('controller');
        if ($controller === 'AdminProducts' || $controller === 'AdminAiProductOptimizer') {
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
            $this->context->controller->addJS($this->_path . 'views/js/admin.js');
        }
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int) $params['id_product'];
        
        if (!$id_product) {
            return '';
        }

        $product = new Product($id_product, false, $this->context->language->id);
        
        $this->context->smarty->assign([
            'product' => $product,
            'id_product' => $id_product,
            'id_lang' => $this->context->language->id,
            'ajax_url' => $this->context->link->getAdminLink('AdminAiProductOptimizer'),
            'provider' => Configuration::get('AIOPT_PROVIDER'),
            'module_path' => $this->_path,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/product_tab.tpl');
    }

    /**
     * Obtener el proveedor de IA configurado
     */
    public function getAIProvider()
    {
        $provider = Configuration::get('AIOPT_PROVIDER');
        
        if ($provider === 'gemini') {
            return new GeminiProvider(
                Configuration::get('AIOPT_GEMINI_KEY'),
                Configuration::get('AIOPT_GEMINI_MODEL')
            );
        }
        
        return new OpenAIProvider(
            Configuration::get('AIOPT_OPENAI_KEY'),
            Configuration::get('AIOPT_OPENAI_MODEL')
        );
    }
}
