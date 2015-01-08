<?php

/**
 * LoginForm class.
 * LoginForm is the data structure for keeping
 * user login form data. It is used by the 'login' action of 'DefaultController'.
 */
class LoginForm extends BasePasswordForm
{
	public $username;
	public $password;
	public $rememberMe;

	/**
	 * @var IdentityInterface cached object returned by @see getIdentity()
	 */
	private $_identity;

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		$rules = array_merge(array(
			array('username, password', 'filter', 'filter'=>'trim'),
			array('username, password', 'required'),
			array('rememberMe', 'boolean'),
			array('password', 'authenticate'),
		), $this->rulesAddScenario(parent::rules(), 'reset'), $this->getBehaviorRules());

		return $rules;
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array_merge($this->getBehaviorLabels(), parent::attributeLabels(), array(
			'username'		=> Yii::t('UsrModule.usr','Username'),
			'password'		=> Yii::t('UsrModule.usr','Password'),
			'rememberMe'	=> Yii::t('UsrModule.usr','Remember me when logging in next time'),
		));
	}

	/**
	 * @inheritdoc
	 */
	public function getIdentity()
	{
		if($this->_identity===null) {
			$userIdentityClass = $this->userIdentityClass;
			$this->_identity=new $userIdentityClass($this->username,$this->password);
			$this->_identity->authenticate();
		}
		return $this->_identity;
	}

	/**
	 * Authenticates the password.
	 * This is the 'authenticate' validator as declared in rules().
	 * @param string $attribute
	 * @param array $params
	 * @return boolean
	 */
	public function authenticate($attribute,$params)
	{
		if($this->hasErrors()) {
			return;
		}
		$identity = $this->getIdentity();
		if (!$identity->getIsAuthenticated()) {
            $this->addError('password', !empty($identity->errorMessage) ? $identity->errorMessage : Yii::t('UsrModule.usr','Invalid username or password.'));
			return false;
		}
		return true;
	}

	/**
	 * A wrapper for the passwordHasNotExpired method from ExpiredPasswordBehavior.
	 * @param $attribute string
	 * @param $params array
	 */
	public function passwordHasNotExpired($attribute, $params)
	{
		if (($behavior=$this->asa('expiredPasswordBehavior')) !== null && $behavior->passwordTimeout !== null) {
			return $behavior->passwordHasNotExpired($attribute, $params);
		}
		return true;
	}

	/**
	 * A wrapper for the validOneTimePassword method from OneTimePasswordBehavior.
	 * @param $attribute string
	 * @param $params array
	 */
	public function validOneTimePassword($attribute, $params)
	{
		if (($behavior=$this->asa('oneTimePasswordBehavior')) !== null && ! $behavior->isMode(UsrModule::OTP_NONE)) {
			return $behavior->validOneTimePassword($attribute, $params);
		}
		return true;
	}

	/**
	 * Resets user password using the new one given in the model.
	 * @return boolean whether password reset was successful
	 */
	public function resetPassword()
	{
		if($this->hasErrors()) {
			return;
		}
		$identity = $this->getIdentity();
		if (!$identity->resetPassword($this->newPassword)) {
			$this->addError('newPassword',Yii::t('UsrModule.usr','Failed to reset the password.'));
			return false;
		}
		return true;
	}

	/**
	 * Logs in the user using the given username and password in the model.
	 * @param integer $duration For how long the user will be logged in without any activity, in seconds.
	 * @return boolean whether login is successful
	 */
	public function login($controller, $duration = 0)
	{
        
		$identity = $this->getIdentity();
		if ($this->scenario === 'reset') {
			$identity->password = $this->newPassword;
			$identity->authenticate();
		}
		if($identity->getIsAuthenticated()) {
			return $controller->module->getUser()->login($identity, $this->rememberMe ? $duration : 0);
		}
		return false;
	}

    public function beforeLogin()
    {
        return $this->onBeforeLogin();
    }

    public function afterLogin()
    {
        return $this->onAfterLogin();
    }

    /**
     * Fire afterLogin events
     * @param CFormModel $model
     */
    public function onBeforeLogin()
    {
        // We nedd to transfer response via CEvent::param property becouse events do not returns result
        $event = new CEvent($this, array('success'=>true));
        $this->raiseEvent('onBeforeLogin', new CEvent($this));
        return isset($event->params['success']) ? $event->params['success'] : true;
    }

    /**
     * Fire afterLogin events
     * @param CFormModel $model
     */
    public function onAfterLogin()
    {
        $this->raiseEvent('onAfterLogin', new CEvent($this, array('success'=>true)));
    }

    /**
     * Attach handler to event.
     * (Chceck if event is correctly prepared)
     *
     * @param CComponent $object
     * @param string $handler
     */
    public function attachHandler($object, $handler)
    {
        $method = 'on' . ucfirst($handler);
        if (method_exists($object, $handler) && method_exists($this, $method)) {
            $this->$method = array($object, $handler);
        }
    }
}
