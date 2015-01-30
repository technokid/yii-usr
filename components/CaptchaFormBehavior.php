<?php
/**
 * CaptchaFormBehavior class file.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */

/**
 * CaptchaFormBehavior adds captcha validation to a form model component.
 * The model should extend from {@link CFormModel} or its child classes.
 *
 * @property CFormModel $owner The owner model that this behavior is attached to.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */
class CaptchaFormBehavior extends FormModelBehavior
{
	public $verifyCode;

	/**
	 * @inheritdoc
	 */
	public function filterRules($rules = array())
	{
		$module = Yii::app()->controller !== null ? Yii::app()->controller->module : null;
		$behaviorRules = array(
			array('verifyCode', 'captcha', 'captchaAction'=>($module !== null ? $module->getId() : 'usr').'/default/captcha'),
		);
		return array_merge($rules, $this->applyRuleOptions($behaviorRules));
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return array(
			'verifyCode' => Yii::t('UsrModule.usr','Verification code'),
		);
	}
}
