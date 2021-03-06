<?php
/**
 * Bitly plugin for Craft CMS 3.x
 *
 * Generate short urls
 *
 * @link      https://github.com/stuart-haas
 * @copyright Copyright (c) 2020 Stuart Haas
 */

namespace stuarthaas\bitly;

use stuarthaas\bitly\base\PluginTrait;
use stuarthaas\bitly\models\Settings;
use stuarthaas\bitly\variables\BitlyVariable;
use stuarthaas\bitly\fields\BitlyField;
use stuarthaas\bitly\twigextensions\BitlyTwigExtension;

use Craft;
use craft\web\twig\variables\CraftVariable;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\services\Plugins;
use craft\services\Fields;
use craft\web\UrlManager;
use craft\web\twig\variables\Cp;

use yii\base\Event;

/**
 * Class Bitly
 *
 * @author    Stuart Haas
 * @package   Bitly
 * @since     1.0.0
 *
 */
class Plugin extends \craft\base\Plugin
{
    // Traits
    // =========================================================================

    use PluginTrait;

    // Static Properties
    // =========================================================================

    public static $plugin;

    // Public Properties
    // =========================================================================

    public $schemaVersion = '1.0.0';
    public $hasCpSettings = true;
    public $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;
        
        $this->setComponents([
            'bitly' => \stuarthaas\bitly\services\Bitly::class,
        ]);

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['bitly/api/create'] = 'bitly/bitly/create';
                $event->rules['bitly/api/update'] = 'bitly/bitly/update';
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['bitly/links'] = 'bitly/bitly/links-index';
                $event->rules['bitly/links/view'] = 'bitly/bitly/view-link';
            }
        );

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = BitlyField::class;
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('bitly', BitlyVariable::class);
            }
        );

        if (Craft::$app->request->getIsSiteRequest()) {
            // Add in our Twig extension
            $extension = new BitlyTwigExtension();
            Craft::$app->view->registerTwigExtension($extension);
        }

        Craft::info(
            Craft::t(
                'bitly',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function getPluginName()
    {
        return Craft::t('bitly', $this->getSettings()->pluginName);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    protected function settingsHtml()
    {
        return \Craft::$app->getView()->renderTemplate('bitly/settings', [
            'settings' => $this->getSettings()
        ]);
    }
}
