<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';

class dreame extends eqLogic {
    /* * *************************Attributs****************************** */

    /*
 * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
 * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
 public static $_widgetPossibility = array();
 */

    /*
 * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
 * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
 public static $_encryptConfigKey = array('param1', 'param2');
 */

    /* * ***********************Methode static*************************** */


    //* Fonction exécutée automatiquement toutes les minutes par Jeedom

    public static function cron() {
        $eqLogics = self::byType('dreame');
        if (count($eqLogics) > 0) {
            /** @var dreame $eqLogic */
            foreach ($eqLogics as $eqLogic) {
                if ($eqLogic->getIsEnable() == 1 && $eqLogic->getConfiguration('model') != 'dreame.vacuum.p2008') {
                    $eqLogic->updateCmd();
                }
            }
        }
    }



    // * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
    public static function cron5() {
        $eqLogics = self::byType('dreame');
        if (count($eqLogics) > 0) {
            /** @var dreame $eqLogic */
            foreach ($eqLogics as $eqLogic) {
                if ($eqLogic->getIsEnable() == 1 && $eqLogic->getConfiguration('model') == 'dreame.vacuum.p2008') {
                    $eqLogic->updateCmd();
                }
            }
        }
    }

    /*
 * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
 public static function cron10() {}
 */

    /*
 * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
 public static function cron15() {}
 */

    /*
 * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
 public static function cron30() {}
 */

    /*
 * Fonction exécutée automatiquement toutes les heures par Jeedom
 public static function cronHourly() {}
 */

    /*
 * Fonction exécutée automatiquement tous les jours par Jeedom
 public static function cronDaily() {}
 */

    /* * *********************Méthodes d'instance************************* */

    // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert() {
    }

    // Fonction exécutée automatiquement après la création de l'équipement
    public function postInsert() {
    }

    // Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate() {
    }

    // Fonction exécutée automatiquement après la mise à jour de l'équipement
    public function postUpdate() {
    }

    // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
    public function preSave() {
    }

    // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
    public function postSave() {
        if ($this->getIsEnable() == 1) $this->createCmd();
    }

    // Fonction exécutée automatiquement avant la suppression de l'équipement
    public function preRemove() {
    }

    // Fonction exécutée automatiquement après la suppression de l'équipement
    public function postRemove() {
    }

    /*
 * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
 * Exemple avec le champ "Mot de passe" (password)
 public function decrypt() {
 $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
 }
 public function encrypt() {
 $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
 }
 */

    /*
 * Permet de modifier l'affichage du widget (également utilisable par les commandes)
 public function toHtml($_version = 'dashboard') {}
 */

    /*
 * Permet de déclencher une action avant modification d'une variable de configuration du plugin
 * Exemple avec la variable "param3"
 public static function preConfig_param3( $value ) {
 // do some checks or modify on $value
 return $value;
 }
 */

    /*
 * Permet de déclencher une action après modification d'une variable de configuration du plugin
 * Exemple avec la variable "param3"
 public static function postConfig_param3($value) {
 // no return value
 }
 */

    /* * **********************Getteur Setteur*************************** */

    public static function detectDevices() {

        log::add(__CLASS__, "debug", "============================ DISCOVER ============================");

        $accountEmail = trim(config::byKey('account-email', __CLASS__));
        $accountPassword = trim(config::byKey('account-password', __CLASS__));
        $accountCountry = trim(config::byKey('account-country', __CLASS__));

        $cmd = system::getCmdSudo() . " micloud get-devices -u '" . $accountEmail . "' -p '" . $accountPassword . "' -c " . $accountCountry . " 2>&1";
        exec($cmd, $outputArray, $resultCode);
        log::add(__CLASS__, "debug", json_encode($outputArray));

        if ($resultCode != 0) {
            if (strstr($outputArray[23], 'Access denied')) { //$outputArray[23] = "micloud.micloudexception.MiCloudAccessDenied: Access denied. Did you set the correct api key and/or username?")
                log::add(__CLASS__, "debug", "Erreur Mot de Passe ou Email");

                event::add('jeedom::alert', array(
                    'level' => 'danger',
                    'page' => 'dreame',
                    'ttl' => 10000,
                    'message' => __('Identification impossible, vérifiez votre identifiant et votre mot de passe Xiami Home.', __FILE__),
                ));
                return [
                    "newEq" => 0,
                ];
            }
        } else {
            $json = json_decode($outputArray[0]);
            log::add(__CLASS__, "debug", json_encode($json));
            $getAllDevices = eqLogic::byType(__CLASS__);
            $numberNewDevice = 0;
            foreach ($json as $response) {
                $alreadyExist = false;
                foreach ($getAllDevices as $device) {
                    if ($device->getLogicalId() == $response->did) {
                        $alreadyExist = true;
                        break;
                    }
                }

                if ($alreadyExist) {
                    if ($device->getConfiguration('ip') != $response->localip || $device->getConfiguration('token') != $response->token) {
                        $device->setConfiguration('ip', $response->localip);
                        $device->setConfiguration('token', $response->token);
                        $device->setConfiguration('modelType', self::getModelType($response->model));
                        $device->save();
                        log::add(__CLASS__, "debug", "Mise à jour de l'IP et du token pour l'équipement existant.");
                    } elseif ($device->getConfiguration('modelType') == '') {
                        $device->setConfiguration('modelType', self::getModelType($response->model));
                        $device->save();
                    } else {
                        log::add(__CLASS__, "debug", "Equipement déjà présent, pas de modification");
                    }
                } else {
                    // Check if $response->model contains 'Dreame' or 'viomi'
                    if (strpos($response->model, 'dreame') !== false || strpos($response->model, 'viomi') !== false || strpos($response->model, 'roborock') !== false) {
                        $eqlogic = new dreame();
                        $eqlogic->setName($response->name);
                        $eqlogic->setIsEnable(1);
                        $eqlogic->setIsVisible(0);
                        $eqlogic->setLogicalId($response->did);
                        $eqlogic->setEqType_name('dreame');
                        $eqlogic->setConfiguration('did', $response->did);
                        $eqlogic->setConfiguration('ip', $response->localip);
                        $eqlogic->setConfiguration('token', $response->token);
                        $eqlogic->setConfiguration('model', $response->model);
                        $eqlogic->setConfiguration('modelType', self::getModelType($response->model));
                        $eqlogic->save();
                        $numberNewDevice++;
                        log::add(__CLASS__, "debug", "Nouvel Equipement, ajout en cours.");
                    } else {
                        log::add(__CLASS__, "debug", "Le modèle de l'équipement n'est pas pris en charge : " . $response->model);
                    }
                }
            }
        }

        return [
            "newEq" => $numberNewDevice,
        ];
    }

    public function createCmd() {

        log::add(__CLASS__, "debug", "============================ CREATING CMD ============================");

        $order = 1;

        $batteryLevel = $this->getCmd(null, 'batteryLevel');
        if (!is_object($batteryLevel)) {
            $batteryLevel = new dreameCmd();
            $batteryLevel->setName(__('Batterie', __FILE__));
            $batteryLevel->setOrder($order++);
            $batteryLevel->setUnite('%');
            $batteryLevel->setIsVisible(1);
            $batteryLevel->setIsHistorized(1);
        }
        $batteryLevel->setLogicalId('batteryLevel');
        $batteryLevel->setEqLogic_id($this->getId());
        $batteryLevel->setType('info');
        $batteryLevel->setSubType('numeric');
        $batteryLevel->setTemplate('dashboard', 'default');
        $batteryLevel->setTemplate('mobile', 'default');
        $batteryLevel->setDisplay('forceReturnLineBefore', false);
        $batteryLevel->save();

        $isCharging = $this->getCmd(null, 'isCharging');
        if (!is_object($isCharging)) {
            $isCharging = new dreameCmd();
            $isCharging->setName(__('Etat de Charge', __FILE__));
            $isCharging->setOrder($order++);
            $isCharging->setIsVisible(1);
            $isCharging->setIsHistorized(1);
        }
        $isCharging->setLogicalId('isCharging');
        $isCharging->setEqLogic_id($this->getId());
        $isCharging->setType('info');
        $isCharging->setTemplate('dashboard', 'default');
        $isCharging->setTemplate('mobile', 'default');
        $isCharging->setSubType('numeric');
        $isCharging->setDisplay('forceReturnLineBefore', false);
        $isCharging->save();

        $error = $this->getCmd(null, 'error');
        if (!is_object($error)) {
            $error = new dreameCmd();
            $error->setName(__('Erreur', __FILE__));
            $error->setOrder($order++);
            $error->setIsVisible(1);
            $error->setIsHistorized(1);
        }
        $error->setLogicalId('error');
        $error->setEqLogic_id($this->getId());
        $error->setType('info');
        $error->setTemplate('dashboard', 'line');
        $error->setTemplate('mobile', 'line');
        $error->setSubType('numeric');
        $error->setDisplay('forceReturnLineBefore', false);
        $error->save();

        $errorDevice = $this->getCmd(null, 'errorDevice');
        if (!is_object($errorDevice)) {
            $errorDevice = new dreameCmd();
            $errorDevice->setName(__('ErreurDevice', __FILE__));
            $errorDevice->setOrder($order++);
            $errorDevice->setIsVisible(1);
            $errorDevice->setIsHistorized(1);
        }
        $errorDevice->setLogicalId('errorDevice');
        $errorDevice->setEqLogic_id($this->getId());
        $errorDevice->setType('info');
        $errorDevice->setTemplate('dashboard', 'line');
        $errorDevice->setTemplate('mobile', 'line');
        $errorDevice->setSubType('numeric');
        $errorDevice->setDisplay('forceReturnLineBefore', false);
        $errorDevice->save();

        $stateDevice = $this->getCmd(null, 'stateDevice');
        if (!is_object($stateDevice)) {
            $stateDevice = new dreameCmd();
            $stateDevice->setName(__('Statut', __FILE__));
            $stateDevice->setOrder($order++);
            $stateDevice->setIsVisible(1);
            $stateDevice->setIsHistorized(1);
        }
        $stateDevice->setLogicalId('stateDevice');
        $stateDevice->setEqLogic_id($this->getId());
        $stateDevice->setType('info');
        $stateDevice->setTemplate('dashboard', 'line');
        $stateDevice->setTemplate('mobile', 'line');
        $stateDevice->setSubType('numeric');
        $stateDevice->setDisplay('forceReturnLineBefore', false);
        $stateDevice->save();

        $statusDevice = $this->getCmd(null, 'statusDevice');
        if (!is_object($statusDevice)) {
            $statusDevice = new dreameCmd();
            $statusDevice->setName(__('Etat', __FILE__));
            $statusDevice->setOrder($order++);
            $statusDevice->setIsVisible(1);
            $statusDevice->setIsHistorized(0);
        }
        $statusDevice->setLogicalId('statusDevice');
        $statusDevice->setEqLogic_id($this->getId());
        $statusDevice->setType('info');
        $statusDevice->setTemplate('dashboard', 'line');
        $statusDevice->setTemplate('mobile', 'line');
        $statusDevice->setSubType('string');
        $statusDevice->setDisplay('forceReturnLineBefore', true);
        $statusDevice->save();

        $timeBrush = $this->getCmd(null, 'timeBrush');
        if (!is_object($timeBrush)) {
            $timeBrush = new dreameCmd();
            $timeBrush->setName(__('Temps restant brosse principale', __FILE__));
            $timeBrush->setOrder($order++);
            $timeBrush->setUnite('h');
            $timeBrush->setIsVisible(1);
            $timeBrush->setIsHistorized(1);
        }
        $timeBrush->setLogicalId('timeBrush');
        $timeBrush->setEqLogic_id($this->getId());
        $timeBrush->setType('info');
        $timeBrush->setTemplate('dashboard', 'line');
        $timeBrush->setTemplate('mobile', 'line');
        $timeBrush->setSubType('numeric');
        $timeBrush->setDisplay('forceReturnLineBefore', false);
        $timeBrush->save();

        $lifeBrush = $this->getCmd(null, 'lifeBrush');
        if (!is_object($lifeBrush)) {
            $lifeBrush = new dreameCmd();
            $lifeBrush->setName(__('Etat brosse principale', __FILE__));
            $lifeBrush->setOrder($order++);
            $lifeBrush->setUnite('%');
            $lifeBrush->setIsVisible(1);
            $lifeBrush->setIsHistorized(1);
        }
        $lifeBrush->setLogicalId('lifeBrush');
        $lifeBrush->setEqLogic_id($this->getId());
        $lifeBrush->setType('info');
        $lifeBrush->setTemplate('dashboard', 'line');
        $lifeBrush->setTemplate('mobile', 'line');
        $lifeBrush->setSubType('numeric');
        $lifeBrush->setDisplay('forceReturnLineBefore', false);
        $lifeBrush->save();

        $timeBrushLeft = $this->getCmd(null, 'timeBrushLeft');
        if (!is_object($timeBrushLeft)) {
            $timeBrushLeft = new dreameCmd();
            $timeBrushLeft->setName(__('Durée de vie restante brosse latérale', __FILE__));
            $timeBrushLeft->setOrder($order++);
            $timeBrushLeft->setUnite('h');
            $timeBrushLeft->setIsVisible(1);
            $timeBrushLeft->setIsHistorized(1);
        }
        $timeBrushLeft->setLogicalId('timeBrushLeft');
        $timeBrushLeft->setEqLogic_id($this->getId());
        $timeBrushLeft->setType('info');
        $timeBrushLeft->setSubType('numeric');
        $timeBrushLeft->setTemplate('dashboard', 'line');
        $timeBrushLeft->setTemplate('mobile', 'line');
        $timeBrushLeft->setDisplay('forceReturnLineBefore', false);
        $timeBrushLeft->save();

        $lifeBrushLeft = $this->getCmd(null, 'lifeBrushLeft');
        if (!is_object($lifeBrushLeft)) {
            $lifeBrushLeft = new dreameCmd();
            $lifeBrushLeft->setName(__('Etat brosse latérale', __FILE__));
            $lifeBrushLeft->setOrder($order++);
            $lifeBrushLeft->setUnite('%');
            $lifeBrushLeft->setIsVisible(1);
            $lifeBrushLeft->setIsHistorized(1);
        }
        $lifeBrushLeft->setLogicalId('lifeBrushLeft');
        $lifeBrushLeft->setEqLogic_id($this->getId());
        $lifeBrushLeft->setType('info');
        $lifeBrushLeft->setTemplate('dashboard', 'line');
        $lifeBrushLeft->setTemplate('mobile', 'line');
        $lifeBrushLeft->setSubType('numeric');
        $lifeBrushLeft->setDisplay('forceReturnLineBefore', false);
        $lifeBrushLeft->save();

        $timeFilterLeft = $this->getCmd(null, 'timeFilterLeft');
        if (!is_object($timeFilterLeft)) {
            $timeFilterLeft = new dreameCmd();
            $timeFilterLeft->setName(__('Durée de vie restante filtre', __FILE__));
            $timeFilterLeft->setOrder($order++);
            $timeFilterLeft->setUnite('h');
            $timeFilterLeft->setIsVisible(1);
            $timeFilterLeft->setIsHistorized(1);
        }
        $timeFilterLeft->setLogicalId('timeFilterLeft');
        $timeFilterLeft->setEqLogic_id($this->getId());
        $timeFilterLeft->setType('info');
        $timeFilterLeft->setSubType('numeric');
        $timeFilterLeft->setTemplate('dashboard', 'line');
        $timeFilterLeft->setTemplate('mobile', 'line');
        $timeFilterLeft->setDisplay('forceReturnLineBefore', false);
        $timeFilterLeft->save();

        $lifeFilterLeft = $this->getCmd(null, 'lifeFilterLeft');
        if (!is_object($lifeFilterLeft)) {
            $lifeFilterLeft = new dreameCmd();
            $lifeFilterLeft->setName(__('Etat Filtre', __FILE__));
            $lifeFilterLeft->setOrder($order++);
            $lifeFilterLeft->setUnite('%');
            $lifeFilterLeft->setIsVisible(1);
            $lifeFilterLeft->setIsHistorized(1);
        }
        $lifeFilterLeft->setLogicalId('lifeFilterLeft');
        $lifeFilterLeft->setEqLogic_id($this->getId());
        $lifeFilterLeft->setType('info');
        $lifeFilterLeft->setTemplate('dashboard', 'line');
        $lifeFilterLeft->setTemplate('mobile', 'line');
        $lifeFilterLeft->setSubType('numeric');
        $lifeFilterLeft->setDisplay('forceReturnLineBefore', false);
        $lifeFilterLeft->save();

        $cleaningTime = $this->getCmd(null, 'cleaningTime');
        if (!is_object($cleaningTime)) {
            $cleaningTime = new dreameCmd();
            $cleaningTime->setName(__('Temps de nettoyage', __FILE__));
            $cleaningTime->setOrder($order++);
            $cleaningTime->setIsVisible(1);
            $cleaningTime->setIsHistorized(1);
            $cleaningTime->setUnite('min');
        }
        $cleaningTime->setLogicalId('cleaningTime');
        $cleaningTime->setEqLogic_id($this->getId());
        $cleaningTime->setType('info');
        $cleaningTime->setTemplate('dashboard', 'line');
        $cleaningTime->setTemplate('mobile', 'line');
        $cleaningTime->setSubType('numeric');
        $cleaningTime->setDisplay('forceReturnLineBefore', false);
        $cleaningTime->save();

        $cleaningArea = $this->getCmd(null, 'cleaningArea');
        if (!is_object($cleaningArea)) {
            $cleaningArea = new dreameCmd();
            $cleaningArea->setName(__('Surface Nettoyée', __FILE__));
            $cleaningArea->setOrder($order++);
            $cleaningArea->setIsVisible(1);
            $cleaningArea->setIsHistorized(1);
            $cleaningArea->setUnite('m2');
        }
        $cleaningArea->setLogicalId('cleaningArea');
        $cleaningArea->setEqLogic_id($this->getId());
        $cleaningArea->setType('info');
        $cleaningArea->setTemplate('dashboard', 'line');
        $cleaningArea->setTemplate('mobile', 'line');
        $cleaningArea->setSubType('numeric');
        $cleaningArea->setDisplay('forceReturnLineBefore', false);
        $cleaningArea->save();

        $stop = $this->getCmd('action', 'stop');
        if (!is_object($stop)) {
            $stop = new dreameCmd();
            $stop->setName(__('Arreter', __FILE__));
            $stop->setOrder($order++);
            $stop->setIsVisible(1);
        }
        $stop->setLogicalId('stop');
        $stop->setEqLogic_id($this->getId());
        $stop->setType('action');
        $stop->setSubType('other');
        $stop->setDisplay('forceReturnLineAfter', true);
        $stop->save();

        $start = $this->getCmd('action', 'start');
        if (!is_object($start)) {
            $start = new dreameCmd();
            $start->setName(__('Démarrer', __FILE__));
            $start->setOrder($order++);
            $start->setIsVisible(1);
        }
        $start->setLogicalId('start');
        $start->setEqLogic_id($this->getId());
        $start->setType('action');
        $start->setSubType('other');
        $start->setDisplay('generic_type', 'ENERGY_ON');
        $start->setDisplay('forceReturnLineAfter', true);
        $start->save();

        $home = $this->getCmd('action', 'home');
        if (!is_object($home)) {
            $home = new dreameCmd();
            $home->setName(__('Maison', __FILE__));
            $home->setOrder($order++);
            $home->setIsVisible(1);
        }
        $home->setLogicalId('home');
        $home->setEqLogic_id($this->getId());
        $home->setType('action');
        $home->setSubType('other');
        $home->setDisplay('generic_type', 'ENERGY_OFF');
        $home->setDisplay('forceReturnLineAfter', true);
        $home->save();

        $position = $this->getCmd('action', 'position');
        if (!is_object($position)) {
            $position = new dreameCmd();
            $position->setName(__('Cherche Moi', __FILE__));
            $position->setOrder($order++);
            $position->setIsVisible(1);
        }
        $position->setLogicalId('position');
        $position->setEqLogic_id($this->getId());
        $position->setType('action');
        $position->setSubType('other');
        $position->setDisplay('forceReturnLineAfter', true);
        $position->save();

        $play_sound = $this->getCmd('action', 'play-sound');
        if (!is_object($play_sound)) {
            $play_sound = new dreameCmd();
            $play_sound->setName(__('Play Sound', __FILE__));
            $play_sound->setOrder($order++);
            $play_sound->setIsVisible(1);
        }
        $play_sound->setLogicalId('play-sound');
        $play_sound->setEqLogic_id($this->getId());
        $play_sound->setType('action');
        $play_sound->setSubType('other');
        $play_sound->setDisplay('forceReturnLineAfter', true);
        $play_sound->save();

        $refresh = $this->getCmd('action', 'refresh');
        if (!is_object($refresh)) {
            $refresh = new dreameCmd();
            $refresh->setName(__('Rafraichir', __FILE__));
            $refresh->setOrder($order++);
            $refresh->setIsVisible(1);
        }
        $refresh->setLogicalId('refresh');
        $refresh->setEqLogic_id($this->getId());
        $refresh->setType('action');
        $refresh->setSubType('other');
        $refresh->setDisplay('forceReturnLineAfter', true);
        $refresh->save();

        $speed = $this->getCmd('action', 'speed');
        if (!is_object($speed)) {
            $speed = new dreameCmd();
            $speed->setName(__('Vitesse', __FILE__));
            $speed->setOrder($order++);
            $speed->setIsVisible(1);
        }
        $speed->setLogicalId('speed');
        $speed->setEqLogic_id($this->getId());
        $speed->setType('action');
        $speed->setSubType('select');
        //Silent (0), Basic (1), Strong (2), Full Speed (3)
        $speed->setConfiguration('listValue', '0|Silencieux;1|Normal;2|Fort;3|Vitesse Maximale');
        $speed->setDisplay('forceReturnLineAfter', true);
        $speed->save();

        $this->updateCmd();
    }

    public function updateCmd() {
        log::add(__CLASS__, "debug", "============================ UPDATING CMD ============================");
        $ip = $this->getConfiguration('ip');
        $token = $this->getConfiguration('token');
        $modelType = $this->getConfiguration('modelType');


        if (!empty($ip) && !empty($token)) {
            $cmd = system::getCmdSudo() . " miiocli -o json_pretty $modelType --ip " . $ip . " --token " . $token . " status 2>&1";
            log::add(__CLASS__, 'debug', 'CMD BY ' . $modelType . " => " . $cmd);
            exec($cmd, $outputArray, $resultCode);
        } else {
            log::add(__CLASS__, 'debug', "updateCmd impossible : Pas d'IP ou pas de Token");
            return;
        }

        $log_output = implode(PHP_EOL, $outputArray);
        log::add(__CLASS__, 'debug', 'JSON Complet ' . $log_output);
        $pos = strpos($log_output, '{');
        $json_string = substr($log_output, $pos);
        $json = json_decode($json_string);

        if ($json === null) {
            log::add(__CLASS__, 'debug', 'Erreur JSON (null) : ' . json_last_error_msg());
            return;
        }
        log::add(__CLASS__, 'debug', 'JSON ' . json_encode($json));

        if ($json != null) {
            if ($modelType == 'dreamevacuum') {
                $this->checkAndUpdateCmd("batteryLevel", $json->{"battery_level"});
                $this->checkAndUpdateCmd("isCharging", $json->{"charging_state"});
                $this->checkAndUpdateCmd("error", $json->{"device_fault"});
                $this->checkAndUpdateCmd("stateDevice", $json->{"device_status"});
                $this->checkAndUpdateCmd("timeBrush", $json->{"brush_left_time"});
                $this->checkAndUpdateCmd("lifeBrush", $json->{"brush_life_level"});
                $this->checkAndUpdateCmd("timeBrushLeft", $json->{"brush_left_time2"});
                $this->checkAndUpdateCmd("lifeBrushLeft", $json->{"brush_life_level2"});
                $this->checkAndUpdateCmd("timeFilterLeft", $json->{"filter_left_time"});
                $this->checkAndUpdateCmd("lifeFilterLeft", $json->{"filter_life_level"});
                $this->checkAndUpdateCmd("cleaningTime", $json->{"cleaning_time"});
                $this->checkAndUpdateCmd("cleaningArea", $json->{"cleaning_area"});
                $this->checkAndUpdateCmd("speed", $json->{"operating_mode"});

                if (($json->{"device_status"} == 2) && ($json->{"charging_state"} == 1)) {
                    $this->checkAndUpdateCmd("statusDevice", "Prêt à démarrer");
                }

                if ($json->{"device_status"} == 1) {
                    $this->checkAndUpdateCmd("statusDevice", "Aspiration en cours");
                }

                if (($json->{"device_status"} == 2) && ($json->{"charging_state"} != 1)) {
                    $this->checkAndUpdateCmd("statusDevice", "Arrêt");
                }

                if (($json->{"device_status"} == 3) && ($json->{"charging_state"} != 1)) {
                    $this->checkAndUpdateCmd("statusDevice", "En pause");
                }

                if ($json->{"device_status"} == 4) {
                    $this->checkAndUpdateCmd("statusDevice", "Erreur");
                }

                if (($json->{"device_status"} == 5) && ($json->{"charging_state"} == 5)) {
                    $this->checkAndUpdateCmd("statusDevice", "Retour maison");
                }

                if (($json->{"device_status"} == 6) && ($json->{"charging_state"} == 1)) {
                    $this->checkAndUpdateCmd("statusDevice", "En charge");
                }

                if ($json->{"device_status"} == 7) {
                    $this->checkAndUpdateCmd("statusDevice", "Aspiration et lavage en cours");
                }

                if ($json->{"device_status"} == 8) {
                    $this->checkAndUpdateCmd("statusDevice", "Séchage de la serpillère");
                }

                if ($json->{"device_status"} == 12) {
                    $this->checkAndUpdateCmd("statusDevice", "Nettoyage en cours de la zone");
                }

                if ($json->{"filter_life_level"} == 51) {
                    $this->checkAndUpdateCmd("errorDevice", "Le filtre est mouillé");
                }

                if ($json->{"filter_life_level"} == 106) {
                    $this->checkAndUpdateCmd("errorDevice", "Vider le bac et nettoyer la planche de lavage.");
                }
            } elseif ($modelType == 'viomivacuum') {
                $this->checkAndUpdateCmd("batteryLevel", $json->{"battary_life"});
                $this->checkAndUpdateCmd("isCharging", $json->{"charging_state"});
                $this->checkAndUpdateCmd("error", $json->{"err_state"});
                $this->checkAndUpdateCmd("stateDevice", $json->{"run_state"});
                $this->checkAndUpdateCmd("timeBrush", "0");
                $this->checkAndUpdateCmd("lifeBrush", "0");
                $this->checkAndUpdateCmd("timeBrushLeft", "0");
                $this->checkAndUpdateCmd("lifeBrushLeft", "0");
                $this->checkAndUpdateCmd("timeFilterLeft", "0");
                $this->checkAndUpdateCmd("lifeFilterLeft", "0");
                $this->checkAndUpdateCmd("cleaningTime", $json->{"s_time"});
                $this->checkAndUpdateCmd("cleaningArea", $json->{"s_area"});
                $this->checkAndUpdateCmd("speed", $json->{"suction_grade"});
            } elseif ($modelType == 'roborockvacuum') {
                $cleanTimeFormatted = gmdate("i", $json->{"clean_time"});
                $cleanAreaFormatted = round($json->{"clean_area"} / 1000000, 2);
                $this->checkAndUpdateCmd("batteryLevel", $json->{"battery"});
                $this->checkAndUpdateCmd("isCharging", $json->{"charge_status"});
                $this->checkAndUpdateCmd("error", $json->{"error_code"});
                $this->checkAndUpdateCmd("stateDevice", $json->{"state"});
                $this->checkAndUpdateCmd("cleaningTime", $cleanTimeFormatted);
                $this->checkAndUpdateCmd("cleaningArea", $cleanAreaFormatted);
                $this->checkAndUpdateCmd("speed", $json->{"fan_power"});
            } else {
                $this->checkAndUpdateCmd("batteryLevel", $json->{"battery:battery-level"});
                $this->checkAndUpdateCmd("isCharging", $json->{"battery:charging-state"});
                $this->checkAndUpdateCmd("error", $json->{"vacuum:fault"});
                $this->checkAndUpdateCmd("stateDevice", $json->{"vacuum:status"});
                $this->checkAndUpdateCmd("timeBrush", $json->{"brush-cleaner:brush-left-time"});
                $this->checkAndUpdateCmd("lifeBrush", $json->{"brush-cleaner:brush-life-level"});
                $this->checkAndUpdateCmd("timeBrushLeft", "0");
                $this->checkAndUpdateCmd("lifeBrushLeft", "0");
                $this->checkAndUpdateCmd("timeFilterLeft", $json->{"filter:filter-left-time"});
                $this->checkAndUpdateCmd("lifeFilterLeft", $json->{"filter:filter-life-level"});
                $this->checkAndUpdateCmd("cleaningTime", $json->{"vacuum-extend:cleaning-time"});
                $this->checkAndUpdateCmd("cleaningArea", $json->{"vacuum-extend:cleaning-area"});
                $this->checkAndUpdateCmd("speed", $json->{"vacuum:mode"});

                if (($json->{"vacuum:status"} == 2) and ($json->{"battery:charging-state"} == 1)) {
                    $this->checkAndUpdateCmd("statusDevice", "Prêt à démarrer");
                }

                if ($json->{"vacuum:status"} == 1) {
                    $this->checkAndUpdateCmd("statusDevice", "Aspiration en cours");
                }

                if (($json->{"vacuum:status"} == 2) and ($json->{"battery:charging-state"} != 1)) {
                    $this->checkAndUpdateCmd("statusDevice", "Arret");
                }

                if (($json->{"vacuum:status"} == 3) and ($json->{"battery:charging-state"} != 1)) {
                    $this->checkAndUpdateCmd("statusDevice", "En Pause");
                }

                if ($json->{"vacuum:status"} == 4) {
                    $this->checkAndUpdateCmd("statusDevice", "Erreur");
                }

                if (($json->{"vacuum:status"} == 5) and ($json->{"battery:charging-state"} == 5)) {
                    $this->checkAndUpdateCmd("statusDevice", "Retour Maison");
                }

                if (($json->{"vacuum:status"} == 6) and ($json->{"battery:charging-state"} == 1)) {
                    $this->checkAndUpdateCmd("statusDevice", "En Charge");
                }

                if ($json->{"vacuum:status"} == 7) {
                    $this->checkAndUpdateCmd("statusDevice", "Aspiration et Lavage en cours");
                }

                if ($json->{"vacuum:status"} == 8) {
                    $this->checkAndUpdateCmd("statusDevice", "Séchage de la serpillère");
                }
                if ($json->{"vacuum:status"} == 12) {
                    $this->checkAndUpdateCmd("statusDevice", "Nettoyage en cours de la Zone");
                }

                if ($json->{"vacuum:fault"} == 51) {
                    $this->checkAndUpdateCmd("errorDevice", "Filtre est mouillé");
                }

                if ($json->{"vacuum:fault"} == 106) {
                    $this->checkAndUpdateCmd("errorDevice", "Vider le bac et nettoyer la planche de lavage.");
                }
            }
        }
    }

    public static function getModelType($model) {

        switch ($model) {
            case 'viomi.vacuum.v8':
                $type = 'viomivacuum';
                break;

            case 'dreame.vacuum.p2008':
                $type = 'dreamevacuum';
                break;

            default:
                if (strpos($model, 'roborock') !== false) {
                    $type = 'roborockvacuum';
                } else {
                    $type = 'genericmiot';
                }
                break;
        }

        return $type;
    }

    public function getEqIcon() {
        $modelType = $this->getConfiguration('modelType', 'unknownvacuum');
        $imgPath = 'plugins/dreame/data/img/' . $modelType . "_icon.png";

        return $imgPath;
    }

    public function sendCmd($cmd) {
        $ip = $this->getConfiguration('ip');
        $token = $this->getConfiguration('token');
        $modelType = $this->getConfiguration('modelType');

        $cmdLabelsByModel = [
            'roborockvacuum' => [
                "home" => "home",
                "start" => "start",
                "stop" => "stop",
                "position" => "",
                "play-sound" => "test_sound_volume",
                "setSpeed" => "set_fan_speed",
            ],
            'viomivacuum' => [
                "home" => "home",
                "start" => "start",
                "stop" => "stop",
                "position" => "find",
                "play-sound" => "",
                "setSpeed" => "set_fan_speed",
            ],
            'dreamevacuum' => [
                "home" => "home",
                "start" => "start",
                "stop" => "stop",
                "position" => "locate",
                "play-sound" => "play_sound",
                "setSpeed" => "set_fan_speed",
            ],
            //default
            'genericmiot' => [
                "home" => "battery:start-charge",
                "start" => "vacuum:start-sweep",
                "stop" => "vacuum:stop-sweeping",
                "position" => "audio:position",
                "play-sound" => "audio:play-sound",
                "setSpeed" => "vacuum:mode",
            ],
        ];

        $call = ($modelType == 'genericmiot') ? ' call' : '';
        $cmdExec = system::getCmdSudo() . " miiocli $modelType --ip $ip --token $token" . $call;

        $cmdLabels = $cmdLabelsByModel[$modelType];

        $cmdLabel = $cmdLabels[$cmd] ?? "";
        if ($cmdLabel == "") {
            log::add(__CLASS__, 'warn', 'Erreur pour action : ' . $this->getLogicalId());
            return;
        }

        if (!empty($ip) && !empty($token)) {
            $finalCmd = "$cmdExec $cmdLabel";

            exec($finalCmd, $outputArray, $resultCode);
            log::add(__CLASS__, 'debug', '[CMD] ' . $finalCmd);
            $this->updateCmd();
        } else {
            log::add(__CLASS__, 'debug', "updateCmd impossible : Pas d'IP ou pas de Token");
        }
    }
}

class dreameCmd extends cmd {
    /* * *************************Attributs****************************** */

    /*
 public static $_widgetPossibility = array();
 */

    /* * ***********************Methode static*************************** */


    /* * *********************Methode d'instance************************* */

    /*
 * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
 public function dontRemoveCmd() {
 return true;
 }
 */

    // Exécution d'une commande
    public function execute($_options = array()) {

        /** @var dreame $eqLogic */
        $eqLogic = $this->getEqLogic(); // Récupération de l’eqlogic
        Log::add('dreame', 'debug', '$_options[] traité: ' . json_encode($_options));

        switch ($this->getLogicalId()) {
            case 'refresh':
                log::add('dreame', 'debug', 'Refresh : ' . $this->getLogicalId());
                $eqLogic->updateCmd();
                break;

            case 'start':
                log::add('dreame', 'debug', 'start : ' . $this->getLogicalId());
                $eqLogic->sendCmd('start');
                break;

            case 'stop':
                log::add('dreame', 'debug', 'stop : ' . $this->getLogicalId());
                $eqLogic->sendCmd('stop');
                break;

            case 'home':
                log::add('dreame', 'debug', 'home : ' . $this->getLogicalId());
                $eqLogic->sendCmd('home');
                break;

            case 'position':
                log::add('dreame', 'debug', 'position : ' . $this->getLogicalId());
                $eqLogic->sendCmd('position');
                break;

            case 'play-sound':
                log::add('dreame', 'debug', 'play-sound : ' . $this->getLogicalId());
                $eqLogic->sendCmd('play-sound');
                break;

            case 'speed':
                log::add('dreame', 'debug', 'speed : ' . $this->getLogicalId());
                $speed = isset($_options['select']) ? $_options['select'] : $_options['slider'];
                $eqLogic->checkAndUpdateCmd('speed', $speed);
                $eqLogic->sendCmd('setSpeed', $speed);
                break;

            default:
                log::add('dreame', 'error', 'Aucune commande associée : ' . $this->getLogicalId());
                break;
        }
    }



    /* * **********************Getteur Setteur*************************** */
}
