<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class SimianGrid
{
    var $_user_cache;

    function SimianGrid()
    {
        $this->ci =& get_instance();
        
        $this->_user_cache = array();
        
        log_message('debug', 'SimianGridClient Initialized');
        
        $this->ci->load->library('Curl');
        
        $this->_init();
    }
    
    function _init()
    {
        $this->grid_service = $this->ci->config->item('grid_service');
        $this->user_service = $this->ci->config->item('user_service');
        $this->asset_service = $this->ci->config->item('asset_service');
        $this->hypergrid_service = $this->ci->config->item('hypergrid_service');
    }

    function _rest_post($url, $params)
    {
        $response = $this->ci->curl->simple_post($url, $params);

        $response = decode_recursive_json($response);

        if (!isset($response))
            $response = array('Message' => 'Invalid or missing response');

        return $response;
    }

    function search_user($thing)
    {
        $query = array(
            'RequestMethod' => 'GetUsers',
            'NameQuery' => $thing
        );
        $response = $this->_rest_post($this->user_service, $query);
        if ( is_array($response['Users']) ) {
            $result = array();
            foreach ( $response['Users'] as $user ) {
                $this_result = array(
                    'name' => $user['Name'],
                    'id' => $user['UserID']
                );
                array_push($result, $this_result);
            }
            return $result;
        }
        return array();
    }
    
    function user_delete($user_id)
    {
        $query = array(
            'RequestMethod' => 'RemoveUser',
            'UserID' => $user_id
        );
        
        $response = $this->_rest_post($this->user_service, $query);
        if (element('Success', $response)) {
            return true;
        } else {
            return false;
        }
    }
    
    function get_user($user_id, $force = false)
    {
        if ( $force || empty($this->_user_cache[$user_id]) ) {
            $user = $this->_user_get('id', $user_id);
            $this->_user_cache[$user_id] = $user;
            return $user;
        } else {
            return $this->_user_cache[$user_id];
        }
    }

    function get_user_by_name($name)
    {
        $user = $this->_user_get('name', $name);
        if ( $user != null ) {
            $this->_user_cache[$user['UserID']] = $user;
        }
        return $user;
    }

    function get_user_by_email($email)
    {
        $user = $this->_user_get('email', $email);
        if ( $user != null ) {
            $this->_user_cache[$user['UserID']] = $user;
        }
        return $user;
    }
    
    function user_id_from_name($name)
    {
        $user = $this->get_user_by_name($name);
        if ( $user === null ) {
            return null;
        } else {
            return $user['UserID'];
        }
    }

    function is_user($user_id)
    {
        $user = $this->user_get($user_id);
        if ( $user == null ) {
            return false;
        } else {
            return true;
        }
    }

    function _user_get($type, $thing)
    {
        // Fetch account data for this user
        if ($type == 'id') {
            $query = array(
                'RequestMethod' => 'GetUser',
                'UserID' => $thing
            );
        } elseif ($type == 'name') {
            $query = array(
                'RequestMethod' => 'GetUser',
                'Name' => $thing
            );
        } elseif ( $type =='email' ) {
            $query = array(
                'RequestMethod' => 'GetUser',
                'Email' => $thing
            );
        } else {
            return;
        }

        $response = $this->_rest_post($this->user_service, $query);

        if (element('Success', $response) && is_array($response['User'])) { 
            return $response['User'];
        } else {
            return;
        }
    }

    function get_identity($identifier)
    {
        $identities = $this->_get_user_identities('name', $identifier);
        if ( count($identities) == 0 ) {
            return null;
        } else {
            return $identities[0];
        }
    }

    function get_user_identities($thing)
    {
        return $this->_get_user_identities('id', $thing);
    }
    
    
    function _get_user_identities($type, $thing)
    {
        if ( $type == 'id' ) {
            $query = array(
                'RequestMethod' => 'GetIdentities',
                'UserID' => $thing
            );
        } else if ( $type == 'name' ) {
            $query = array(
                'RequestMethod' => 'GetIdentities',
                'Identifier' => $thing
            );
        }

        $response = $this->_rest_post($this->user_service, $query);
        if (element('Success', $response) && is_array($response['Identities'])) {
            return $response['Identities'];
        } else {
            return null;
        }
    }
    
    function auth_user($username, $password)
    {
        return $this->authorize_identity('md5hash', $username, '$1$' . md5($password));
    }
    
    function auth_openid($openid)
    {
        return $this->authorize_identity('openid', $openid);
    }
    
    function auth_facebook($token)
    {
        return $this->authorize_identity('facebook', $token);
    }

    function authorize_identity($type, $identifier, $credential='') {
        $query = array(
            'RequestMethod'  => 'AuthorizeIdentity',
            'Identifier'     => $identifier,
            'Credential'     => $credential,
            'Type'           => $type
        );

        $response = $this->_rest_post($this->user_service, $query);

        if (element('Success', $response, false) ) {
            return element('UserID', $response);
        } else {
            return null;
        }
    }
    
    function add_capability($owner_id, $resource, $expiration_timestamp)
    {
        $query = array(
            'RequestMethod' => 'AddCapability',
            'OwnerID' => $owner_id,
            'Resource' => $resource,
            'Expiration' => $expiration_timestamp
        );
        $response = $this->_rest_post($this->user_service, $query);
        if ( element('Success', $response, false) ) {
            return element('CapabilityID', $response, null);
        } else {
            return null;
        }
    }

    function set_user_data($user_id, $key, $value)
    {
        $query = array(
            'RequestMethod' => 'AddUserData',
            'UserID' => $user_id,
             $key => $value
        );
        $response = $this->_rest_post($this->user_service, $query);
        if ( element('Success', $response, false) ) {
            return true;
        } else {
            return false;
        }
    }

    function remove_user_data($user_id, $key)
    {
        $query = array(
            'RequestMethod' => 'RemoveUserData',
            'UserID' => $user_id,
            'Key' => $key
        );
        $this->_rest_post($this->user_service, $query);
    }

    function search_scene($name) {
        return $this->_search_scene('name', $name);
    }
    
    function get_hyperlinks($enabled = true)
    {
        return $this->_search_scene('hypergrid', $enabled);
    }

    function _search_scene($request, $thing)
    {
        if ( $request == "name" ) {
            $query = array(
                'RequestMethod' => 'GetScenes',
                'NameQuery' => $thing
            );
        } else if ( $request === 'hypergrid' ) {
            $query = array(
                'RequestMethod' => 'GetScenes',
                'HyperGrid' => 'true',
                'Enabled' => $thing
            );
        } else {
            return null;
        }
        $response = $this->_rest_post($this->grid_service, $query);
        if ( is_array($response['Scenes']) ) {
            $result = array();
            foreach ( $response['Scenes'] as $scene ) {
                $this_result = array(
                    'name' => $scene['Name'],
                    'id' => $scene['SceneID'],
                    'x' => $scene['MinPosition'][0] / 256,
                    'y' => $scene['MinPosition'][1] / 256
                );
                array_push($result, $this_result);
            }
            return $result;
        }
        return null;
    }
    
    function set_scene_data($scene_id, $key, $value)
    {
        $query = array(
            'RequestMethod' => 'AddSceneData',
            'SceneID' => $scene_id,
            'Key' => $key,
            'Value' => $value
        );
        $response = $this->_rest_post($this->grid_service, $query);
        if ( element('Success', $response, false) ) {
            return true;
        } else {
            return false;
        }
    }

    function get_scene($scene_id)
    {
        return $this->_get_scene_info('id', $scene_id);
    }
    
    function get_scene_by_name($name)
    {
        $result = $this->_search_scene('name', $name);
        if ( $result != null && count($result) > 0 ) {
            return $this->get_scene($result[0]['id']);
        } else {
            return null;
        }
    }
    
    function get_scene_by_pos($x, $y)
    {
        return $this->_get_scene_info('pos', "<$x, $y, 0>");
    }
    
    function get_scene_by_owner($owner)
    {
        return $this->_get_scene_info('owner', $owner);
    }

    function _get_scene_info($request, $thing = null)
    {
        if ( $request == "id" ) {
            $query = array(
                'RequestMethod' => 'GetScene',
                'SceneID' => $thing
            );
        } else if ( $request == "pos" ) {
            $query = array(
                'RequestMethod' => 'GetScene',
                'Position' => $thing
            );
        } else if ( $request == "name" ) {
            $query = array(
                'RequestMethod' => 'GetScene',
                'Name' => $thing
            );
        } else if ( $request == "owner" ) {
            $query = array(
                'RequestMethod' => 'GetScene',
                'EstateOwner' => $thing
            );
        } else {
            return null;
        }

        $response = $this->_rest_post($this->grid_service, $query);
        if (element('Success', $response) ) {
            return $response;
        } else {
            return;
        }
    }
    
    function get_texture($uuid, $x = null, $y = null)
    {   
        $image = $this->ci->curl->simple_get($this->asset_service . $uuid);

        if ( $image == null ) {
            return null;
        }

        $im = new imagick();
        $im->readImageBlob($image);

        $im->setImageFormat("jpeg");
        if ( $x != null && $y != null ) {
            $im->scaleImage(200, 200, true);
        }
        return $im;
    }

    function register($username, $email)
    {
        $userid = random_uuid();
        $query = array(
            'RequestMethod' => 'AddUser',
            'UserID' => $userid,
            'Name' => $username,
            'Email' => $email
        );
        $response = $this->_rest_post($this->user_service, $query);
        if (element('Success', $response)) {
            return $userid;
        } else {
            return null;
        }
    }

    function identity_set($user_id, $type, $identifier, $credential='')
    {
        $query = array(
            'RequestMethod' => 'AddIdentity',
            'Identifier' => $identifier,
            'Credential' => $credential,
            'Type' => $type,
            'UserID' => $user_id
        );

        $response = $this->_rest_post($this->user_service, $query);
        
        if (element('Success', $response)) {
            return true;
        } else {
            return false;
        }
    }
    
    function identity_remove($user_id, $type, $identifier)
    {
        $query = array(
            'RequestMethod' => 'RemoveIdentity',
            'Type' => $type,
            'Identifier' => $identifier,
            'UserID' => $user_id
        );
        
        $response = $this->_rest_post($this->user_service, $query);

        if (element('Success', $response)) {
            return true;
        } else {
            return false;
        }
    }
    
    function create_avatar($user_id, $avtype)
    {
        $query = array(
            'RequestMethod' => 'AddInventory',
            'AvatarType' => $avtype,
            'OwnerID' => $user_id
        );
        
        $response = $this->_rest_post($this->user_service, $query);
        
        if (element('Success', $response)) {
            return true;
        } else {
            return false;
        }
    }
    
    function set_access_level($user_id, $level)
    {
        $user_data = $this->get_user($user_id, true);
        $query = array(
            'RequestMethod' => 'AddUser',
            'UserID' => $user_data['UserID'],
            'Email' => $user_data['Email'],
            'Name' => $user_data['Name'],
            'AccessLevel' => $level
        );
        $response = $this->_rest_post($this->user_service, $query);
        if (element('Success', $response)) {
            return true;
        } else {
            return false;
        }
    }
    
    function simulator_details($scene_id)
    {
        $scene = $this->get_scene($scene_id);
        if ( $scene == null ) {
            return null;
        }
        $url = $scene['Address'] . "monitorstats/$scene_id";
        $url2 = $scene['Address'] . "jsonSimStats";
        $result = $this->ci->curl->simple_get($url);
        if ( $result == null ) {
            log_message('debug', "Unable to retrieve simulator stats from $url");
            return null;
        }
        $result2 = $this->ci->curl->simple_get($url2);
        $json_result = array();
        if ( $result2 == null ) {
            log_message('debug', "Unable to retrieve instance information from $result2");
            $json_result['Version'] = "??";
            $json_result['Uptime'] = "??";
        } else {
            $json_result = decode_recursive_json($result2);
        }
        
        $xml = new SimpleXMLElement($result);
        
        $result = array(
            'simulation_fps' => $xml->SimFPSMonitor,
            'physics_fps' => $xml->PhysicsFPSMonitor,
            'version' => $json_result['Version'],
            'uptime' => $json_result['Uptime'],
            'dilation' => $xml->TimeDilationMonitor,
            'prim_count' => $xml->ObjectCountMonitor,
            'agents' => $xml->AgentCountMonitor,
            'child_agents' => $xml->ChildAgentCountMonitor
        );
        return $result;
    }

    function total_user_count()
    {
        $data = array(
            'RequestMethod' => 'GetUserStats'
        );
        $result = $this->_rest_post($this->user_service, $data);
        if ( isset($result['Success']) ) {
            return $result['UserCount'];
        } else {
            log_message('error', "Unknown response to GetUserStats. Returning 0.");
            return 0;
        }
    }

    function total_scene_count()
    {
        $data = array(
            'RequestMethod' => 'GetSceneStats'
        );
        $result = $this->_rest_post($this->grid_service, $data);
        if ( isset($result['Success']) ) {
            return $result['SceneCount'];
        } else {
            log_message('error', "Unknown response to GetSceneStats. Returning 0.");
            return 0;
        }
    }
    
    function link_region($hg_uri, $region_name, $x, $y)
    {
        $data = array(
            'x' => $x,
            'y' => $y,
            'hg_uri' => $hg_uri,
            'region_name' => $region_name
        );
        $result = $this->_rest_post($this->hypergrid_service . '/sg_api/link_remote', $data);
        return $result['Success'];
    }
}

?>