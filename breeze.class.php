<?php

/**   root directory */
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__FILE__) . '/');
    require(PROJECT_ROOT . 'Autoloader.php');
}

class breeze extends base {

    protected $_curl;
    protected $_token = 'HASH-BREEZE-USER-ACCOUNT';
    protected $_path_breeze = 'https://api.breeze.pm/';
    public $errors;
    protected $_stages = [];

    public function __construct()
    {
        $this->_curl = new breezeCurl();
        $this->errors = new breezeErrors();
    }

    private function _breezeCurlResult()
    {
        $result = null;

        if ($this->_curl->error) {
            $this->errors->add($this->errors->getERRORSYSTEM(), $this->_curl->error_message);
        } else {
            $result = json_decode($this->_curl->response);
        }

        return $result;
    }

    private function _breezeGetCurl($url)
    {
        $url = $this->_path_breeze . $url;

        $this->_curl->get($url, array(
            'api_token' => $this->_token
        ));

        return $this->_breezeCurlResult();
    }

    protected function _breezeDeleteCurl($url)
    {
        $url = $this->_path_breeze . $url;

        $this->_curl->delete($url, array(
            'api_token' => $this->_token
        ));

        return $this->_breezeCurlResult();
    }

    protected function _breezePostCurl($url, $data)
    {
        $url = $this->_path_breeze . $url;

        $data = (string) json_encode($data);

        $this->_curl->setHeader('Content-Type', 'application/json');
        $this->_curl->post($url, $data, $this->_token);

        return $this->_breezeCurlResult();
    }

    protected function _breezePutCurl($url, $data)
    {
        $url = $this->_path_breeze . $url;

        $data = (string) json_encode($data);

        $this->_curl->setHeader('Content-Type', 'application/json');
        $this->_curl->put($url, $data, $this->_token);

        return $this->_breezeCurlResult();
    }

    protected function _breezeIsStagesLoaded($project_id)
    {
        $found = false;

        foreach($this->_stages as $stage){
            if($stage->project_id && $stage->project_id == $project_id){
                $found = true;
            }else{
                $found = false;
                break;
            }
        }

        return $found;
    }

    protected function _breezeGetStages($project_id)
    {
        if(!$this->_breezeIsStagesLoaded($project_id)){

            $result = $this->_breezeGetCurl('projects/' . $project_id . '/stages.json');

            for($i = 0; $i < count($result); $i++){
                $result[$i]->project_id = $project_id;
            }

            $this->_stages = $result;
        }

        return $this->_stages;
    }

    private function _breezeSetStage($project_id, $stage)
    {
        for($i = 0; $i < count($this->_stages); $i++){

            if(strtolower($this->_stages[$i]->name) == strtolower($stage->name)){
                $this->_stages[$i]->project_id = $project_id;
                $this->_stages[$i]->id = $stage->id;
                break;
            }
        }
    }

    private function _breezeFindStage($project_id, $stage_name)
    {
        foreach($this->_stages as $stage){

            if($stage->project_id == $project_id && $stage->name == $stage_name){
                return $stage;
            }
        }

        return null;
    }

    public function breezeGetStageByName($project_id, $new_stage)
    {
        $stages = $this->_breezeGetStages($project_id);

        foreach($stages as $stage){
            if($stage->name == $new_stage){
                return $stage;
            }
        }

        return null;
    }

    public function breezeCreateStages($project_id)
    {
        foreach($this->_stages as $stage){
            $this->_breezePostCurl('projects/' . $project_id . '/stages.json', [
                "name" => $stage->name
            ]);
        }
    }

    public function breezeDeleteDefaultStages($project_id)
    {
        $defaults = 3;
        $index = 0;

        $stages = $this->_breezeGetStages($project_id);

        foreach($stages as $stage){

            $this->_breezeDeleteCurl('projects/' . $project_id . '/stages/' . $stage->id . '.json');

            if(++$index >= $defaults){
                break;
            }
        }
    }

    public function breezeCreateProject($name, $description)
    {
        $result = $this->_breezePostCurl('projects.json', [
            "name" => $name,
            "description" => $description
        ]);

        return $result;
    }

    public function breezeGetCardsByStage($stage)
    {
        $result = $this->_breezeGetCurl('projects/' . $stage->project_id . '/stages/' . $stage->id . '/cards.json');

        return $result;
    }

    protected function _breezeGetCardByStageTag($stage, $tags, $excluded_tags = [])
    {
        $cards = $this->breezeGetCardsByStage($stage);

        $tags = !is_array($tags) ? [$tags]: $tags;
        $excluded_tags = !is_array($excluded_tags) ? [$excluded_tags]: $excluded_tags;

        foreach($cards as $card){

            $founds = 0;

            foreach($tags as $tag) {

                if (in_array($tag, $card->tags)) {
                    $founds++;
                }
            }

            foreach($excluded_tags as $tag) {

                if (in_array($tag, $card->tags)) {
                    $founds--;
                }
            }

            if($founds == count($tags)){
                return $card;
            }
        }

        return null;
    }

    public function breezeCreateCard($project_id, $stage_id, $name, $description)
    {
        $result = $this->_breezePostCurl('projects/' . $project_id . '/cards.json', [
            "name" => $name,
            "description" => $description,
            "stage_id" => $stage_id
        ]);

        return $result;
    }

    public function breeze_delete_card($card)
    {
        $result = $this->_breezeDeleteCurl('projects/' . $card->project->id . '/cards/' . $card->id . '.json');

        return $result;
    }

    public function breezeUpdateCard($card, $values = [])
    {
        $result = $this->_breezePutCurl('projects/' . $card->project->id . '/cards/' . $card->id . '.json', $values);

        return $result;
    }

    public function breezeAddTagToCard($card, $tags)
    {
        $result = $this->_breezePutCurl('projects/' . $card->project->id . '/cards/' . $card->id . '.json', ["tags" => [$tags]]);

        return $result;
    }

    public function breezeGetComments($card)
    {
        $result = $this->_breezeGetCurl('projects/' . $card->project->id . '/cards/' . $card->id . '/comments.json');

        return $result;
    }

    public function breezeAddComment($card, $comment)
    {
        $result = $this->_breezePostCurl('projects/' . $card->project->id . '/cards/' . $card->id . '/comments.json', [
            "comment" => $comment
        ]);

        return $result;
    }

    public function breezeAssignCard($card, $email)
    {
        $result = $this->_breezePostCurl('projects/' . $card->project->id . '/cards/' . $card->id . '/people.json', [
            "invitees" => [$email]
        ]);

        return $result;
    }

    public function breezeMoveCard($card, $stage)
    {
        $stage_id = is_numeric($stage) ? $stage : $this->_breezeGetStageId($card->project->id, $stage);

        if($stage_id != null){
            $this->_breezePutCurl('projects/' . $card->project->id . '/cards/' . $card->id . '.json', ["stage_id" => $stage_id, "prev_id" => $card->stage_id]);
        }else{
            $this->breezeAddError($card, $this->errors->getERRORSYSTEM(), "an error occurred: it was impossible to move the card. Stage id doesn't found.<br>Location: breeze.class.php->breezeMoveCard");
        }
    }

    private function _breezeGetStageId($project_id, $stage_name)
    {
        $stages = $this->_breezeGetStages($project_id);

        foreach($stages as $stage) {
            if(strtolower($stage->name) == strtolower($stage_name)){
                return $stage->id;
            }
        }

        return null;
    }

    public function breezeGetErrors()
    {
        return $this->errors->breezeErrorsGetErrors();
    }

    public function breezeNotifyErrorManager($card, $message)
    {
        $user_name = $this->_breezeGetUserManager();

        $message = $this->breezeCreateNotification($user_name) . ' ' . $message;

        $this->breezeAddComment($card, $message);
    }

    private function _breezeGetUserManager()
    {
        return 'USER_NAME';
    }

    public function breezeCreateNotification($user_name)
    {
        $user_alias = '<b>@' . str_replace(' ', '', $user_name) . '</b>';
        //$user_alias = $user->email;

        return $user_alias;
    }

    public function breezeAddError($card, $type, $description)
    {
        if($type == $this->errors->getERRORSYSTEM()){
            $this->breezeNotifyErrorManager($card, $description);
        }else{
            $this->errors->add($type, $description);
        }
    }
}

class breezeCurl extends Curl{

    function put($url, $data=array(), $token) {
        $this->setopt(CURLOPT_URL, $url . '?api_token=' . $token);
        $this->setopt(CURLOPT_POSTFIELDS, $data);
        $this->setopt(CURLOPT_CUSTOMREQUEST, 'PUT');
        $this->_exec();
    }

    function post($url, $data=array(), $token) {
        $this->setopt(CURLOPT_URL, $url . '?api_token=' . $token);
        $this->setopt(CURLOPT_POST, TRUE);
        $this->setopt(CURLOPT_POSTFIELDS, $data);
        $this->setopt(CURLOPT_CUSTOMREQUEST, 'POST');
        $this->_exec();
    }

    function get($url, $data=array()) {
        $this->setopt(CURLOPT_URL, $url . '?' . http_build_query($data));
        $this->setopt(CURLOPT_HTTPGET, TRUE);
        $this->setopt(CURLOPT_CUSTOMREQUEST, 'GET');
        $this->_exec();
    }
}
