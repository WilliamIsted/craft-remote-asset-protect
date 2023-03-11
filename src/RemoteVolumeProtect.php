<?php
namespace williamisted\remotevolumeprotect;

use Craft;
use craft\elements\Asset;
use craft\events\ModelEvent;
use craft\events\PluginEvent;
use craft\services\Plugins;
use craft\services\Volumes;
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;

use williamisted\remotevolumeprotect\models\Settings;

class RemoteVolumeProtect extends craft\base\Plugin
{
    public $settingsSchema = '1.0.0';

    private $initSettings = false;
    private $env = null;
    private $environmentsTable = [];
    private $permissionsTable = [];
    private $environments = [];

    private $commonEnvironments = [];
    private $uncommonEnvironments = [];

    public function init()
    {
        parent::init();

        $this->env = Craft::$app->env;

        $this->environmentsTable = (object) [
            'dev'           => (int) 0,
            'staging'       => (int) 1,
            'production'    => (int) 2
        ];

        $this->permissionsTable = (object) [
            'create'        => (int) 1, // Add
            // 'read'          => (int) 2, // Not currently used, may be added in future version
            'update'        => (int) 2, // Move
            'delete'        => (int) 3  // Delete
        ];

        $this->commonEnvironments = $this->getSettings()->commonEnvironments;
        $this->commonEnvironments[0][0] = 'dev';
        $this->commonEnvironments[1][0] = 'staging';
        $this->commonEnvironments[2][0] = 'production';

        $this->uncommonEnvironments = $this->getSettings()->uncommonEnvironments;

        foreach ( array_merge( $this->commonEnvironments, $this->uncommonEnvironments ) as $env ) {
            $this->environments[ $env[0] ] = $env;
        }

        Event::on(
            Plugins::class,
            Plugins::EVENT_BEFORE_INSTALL_PLUGIN,
            function (craft\events\PluginEvent $event) {
                
                // Respect existing project config.
                if ( Craft::$app->projectConfig->get('plugins.remotevolumeprotect', true) === null ) {
                    $this->initSettings = true;
                }

            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (craft\events\PluginEvent $event) {

                if ('remotevolumeprotect' === $event->plugin->handle) {
                
                    // Was existing project config found? Should plugin initialize defaults
                    if ( $this->initSettings ) {

                        // Initialize defaults
                        Craft::$app->getPlugins()->savePluginSettings($this, [
                            'settingsSchema' => $this->settingsSchema,
                            'commonEnvironments' => [
                                [null,0,0,0],
                                [null,0,0,0],
                                [null,1,1,1]
                            ]
                        ]);

                    }

                    // Continue with the installation
                    parent::install();

                }
            }
        );

        // Listen for asset creation
        Event::on(
            Asset::class,
            Asset::EVENT_BEFORE_SAVE,
            function(ModelEvent $event) {

                // Only consider deletion if ModelEvent, and is not a typical local volume
                if ( $event instanceof ModelEvent && !$event->sender->getVolume() instanceof craft\volumes\Local ) {

                    if ( in_array( $event->sender->getScenario(), [ ASSET::SCENARIO_CREATE, ASSET::SCENARIO_REPLACE ] ) ) {
                     
                        $canCreate = false;

                        if ( array_key_exists( $this->env, $this->environments ) ) {
                            if ( $this->environments[ $this->env ][ $this->permissionsTable->create ] ) {
                                $canCreate = true;
                            }
                        }

                        if ( !$canCreate ) {
                            $event->isValid = false;
                            throw new \yii\base\Exception('Assets cannot be created in this environment.');
                        }

                    }

                }
            }
        );

        Event::on(
            Asset::class,
            Asset::EVENT_BEFORE_SAVE,
            function(ModelEvent $event) {

                if ( $event instanceof ModelEvent ) {

                    // Only run for expected scenarios
                    if ( !in_array( $event->sender->getScenario(), [ ASSET::SCENARIO_MOVE ] ) ) {
                        return true;
                    }

                    preg_match( '/\{folder:(\d+)\}/', $event->sender->newLocation, $matches );
                    $folderId = $matches[1];

                    $volumeFrom = $event->sender->getVolume();
                    $volumeTo   = Craft::$app->getAssets()->getFolderById( $folderId )->getVolume();

                    // Only run if asset is moving from or to a non-local folder
                    if ( $volumeFrom instanceof craft\volumes\Local && $volumeTo instanceof craft\volumes\Local ) {
                        return true;
                    }

                    $canMove = false;

                    if ( array_key_exists( $this->env, $this->environments ) ) {
                        if ( $this->environments[ $this->env ][ $this->permissionsTable->update ] ) {
                            $canMove = true;
                        }
                    }

                    if ( !$canMove ) {
                        $event->isValid = false;
                        throw new \yii\base\Exception('Assets cannot be moved in this environment.');
                    }

                }

            }
        );

        // Listen for asset deletion
        Event::on(
            Asset::class,
            Asset::EVENT_BEFORE_DELETE,
            function(ModelEvent $event) {

                // Only consider deletion if ModelEvent, and is not a typical local volume
                if ( $event instanceof ModelEvent && !$event->sender->getVolume() instanceof craft\volumes\Local ) {

                    $canDelete = false;

                    if ( array_key_exists( $this->env, $this->environments ) ) {
                        if ( $this->environments[ $this->env ][ $this->permissionsTable->delete ] ) {
                            $canDelete = true;
                        }
                    }

                    if ( !$canDelete ) {
                        $event->isValid = false;
                        throw new \yii\base\Exception('Assets cannot be deleted in this environment.');
                    }

                }
            }
        );

    }

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    protected function settingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('remotevolumeprotect/settings', [
            'settings' => $this->getSettings(),
        ]);
    }

}
