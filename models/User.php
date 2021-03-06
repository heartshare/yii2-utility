<?php

namespace drodata\models;

use Yii;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\base\InvalidParamException;
use yii\data\ActiveDataProvider;
use yii\web\IdentityInterface;
use drodata\helpers\Html;
use drodata\helpers\Utility;
use drodata\behaviors\TimestampBehavior;
use drodata\behaviors\BlameableBehavior;

/**
 * This is the model class for table "{{%user}}".
 * 
 * @property integer $id
 * @property string $username
 * @property string $mobile_phone
 * @property string $auth_key
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $access_token
 * @property string $email
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 */
class User extends \yii\db\ActiveRecord implements IdentityInterface
{
    const STATUS_FROZEN = 0;
    const STATUS_ACTIVE = 1;

    const EVENT_AFTER_LOGIN = 'after-login';

    public function init()
    {
        parent::init();
        //$this->on(self::EVENT_AFTER_INSERT, [$this, 'handlerName']);
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }


    /**
     * @inheritdoc
     * @return UserQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UserQuery(get_called_class());
    }

    /**
     * key means scenario names
     */
    public function transactions()
    {
        return [
            'default' => self::OP_ALL,
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::className(),
            ],
        ];
    }

    /**
     * @inheritdoc
     *
    public function fields()
    {
        $fields = parent::fields();
        
        // 删除涉及敏感信息的字段
        //unset($fields['auth_key']);
        
        // 增加自定义字段
        return ArrayHelper::merge($fields, [
            'time' => function () {
                return $this->readableCreateTime;
            },
            'creator' => function () {
                return $this->readableCreator;
            },
        ]);
    }
    */

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'status'], 'required'],
            [['status', 'created_at', 'updated_at'], 'integer'],
            [['username', 'password_hash', 'password_reset_token', 'access_token', 'email'], 'string', 'max' => 255],
            [['mobile_phone'], 'string', 'max' => 11],
            [['auth_key'], 'string', 'max' => 32],
            [['username'], 'unique'],
            [['access_token'], 'unique'],
            [['mobile_phone'], 'unique'],
            [['password_reset_token'], 'unique'],
            [['email'], 'unique'],
        ];
        //['passwordOld', 'inlineV'],
        /*
            [
                'billing_period', 'required', 
                'when' => function ($model, $attribute) {
                    return $model->payment_way != self::PAYMENT_WAY_SINGLE;
                },
                'on' => self::SCENARIO_ACCOUNTANT,
                'whenClient' => "function (attribute, value) {
                    return $('#company-payment_way input:checked').val() != '1';
                }",
            ],
        */
    }

    /* inline validator
    public function inlineV($attribute, $params, $validator)
    {
        if ($this->$attribute != 'a') {
            $this->addError($attribute, 'error message');
            return false;
        }
        return true;
    }
    */

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::find()->active()->andWhere([
            'id' => $id,
        ])->one();
    }
    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }
    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::find()->active()->andWhere([
            'username' => $username,
        ])->one();
    }
    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }
        return static::find()->active()->andWhere([
            'password_reset_token' => $token,
        ])->one();
    }
    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }
    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }
    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }
    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /*
     * @return array RBAC 角色 name => description 映射
     */
    public static function roleList()
    {
        $auth = Yii::$app->authManager;

        $roles = [];
        foreach ($auth->getRoles() as $name => $role)
        {
            $roles[$name] = $role->description;
        }

        return $roles;
    }

    /**
     * 生成加密密码
     *
     * 由 self::EVENT_BEFORE_INSERT 触发
     *
     * $event->data contains original password
     */
    public function generatePassword($event)
    {
        $this->setPassword($event->data);
    }

    /**
     * 保存用户角色
     *
     * 由 self::EVENT_AFTER_INSERT, self::EVENT_AFTER_UPDATE 触发
     *
     * @param array $event->data 角色数组
     */
    public function saveRoles($event)
    {
        $roles = $event->data;
        $auth = Yii::$app->authManager;

        // 清空旧的角色
        $auth->revokeAll($this->id);

        foreach ($roles as $roleName) {
            $role = $auth->getRole($roleName);
            $auth->assign($role, $this->id);
        }
    }

    /**
     * 生成随机 auth_key 值
     *
     * 由 self::EVENT_BEFORE_INSERT 触发
     */
    public function generateAuthKey($event)
    {
        $this->auth_key = Yii::$app->security->generateRandomString(32);
    }
    /**
     * 生成随机 access_token 值
     *
     * 由 self::EVENT_BEFORE_INSERT 触发
     */
    public function generateAccessToken($event)
    {
        $this->access_token = Yii::$app->security->generateRandomString(60);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => '用户名',
            'mobile_phone' => '手机号',
            'auth_key' => 'Auth Key',
            'password_hash' => 'Password Hash',
            'password_reset_token' => 'Password Reset Token',
            'access_token' => 'Access Token',
            'email' => '邮箱地址',
            'status' => '状态',
            'created_at' => '创建时间',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Render a specified action link, which is usually used in 
     * GridView or ListView.
     *
     * @param string $action action name
     * @param string $type link type, 'icon' and 'button' are available,
     * the former is used in action column in grid view, while the latter
     * is use in list view.
     * @return mixed the link html content
     */
    public function actionLink($action, $type = 'icon')
    {
        $route = '/user/' . $action;
        switch ($action) {
            case 'view':
                return Html::actionLink(
                    [$route, 'id' => $this->id],
                    [
                        'type' => $type,
                        'title' => '详情',
                        'icon' => 'eye',
                        // comment the next line if you don't want to view model in modal.
                        'class' => 'modal-view',
                    ]
                );
                break;
            case 'update':
                return Html::actionLink(
                    [$route, 'id' => $this->id],
                    [
                        'type' => $type,
                        'title' => '修改',
                        'icon' => 'pencil',
                        'visible' => true, //Yii::$app->user->can(''),
                        'disabled' => false,
                        'disabledHint' => '',
                    ]
                );
                break;
            case 'delete':
                return Html::actionLink(
                    [$route, 'id' => $this->id],
                    [
                        'type' => $type,
                        'title' => '删除',
                        'icon' => 'trash',
                        'color' => 'danger',
                        'data' => [
                            'method' => 'post',
                            'confirm' => $this->getConfirmText($action),
                        ],
                        'visible' => false, //Yii::$app->user->can(''),
                        'disabled' => false,
                        'disabledHint' => '',
                    ]
                );
                break;
        }
    }

    /**
     * 获取 POST 操作前的 confirm 文本内容
     *
     * 模型中有很多类似删除这样的操作：没有视图文件，直接通过控制器完成操作，
     * 操作完成后页面跳转至 referrer 而非首页。这类操作前都需要让客户再次确认。
     *
     * @param string $action 对应 actionLink() 中 $action 值
     */
    public function getConfirmText($action = 'delete')
    {
        switch ($action) {
            case 'delete':
                return "请再次确认删除操作。";
                break;
        }
    }

    /**
     * 从 lookup 表中查询对应的记录的 name 值。
     * 当模型中有多个列使用了 lookup 后，就需要声明多个类似下面的 getter:
     *
     * ```php
     * public function getReadableStatus()
     * {
     *     return Lookup::item('Status', $this->status);
     * }
     * ```
     *
     * 此方法旨在减少上面代码的个数。这在模型详情页非常好用，
     * 且不必添加 backend\models\Lookup 命名空间。
     *
     * @param string type lookup 表中 type 列值
     * @param string code lookup 表中 code 列值
     * @return string|null name 值. 未找到记录时返回 null
     */
    public function lookup($type, $code)
    {
        return Lookup::item($type, $code);
    }

    /**
     * 所有用户map
     */
    public static function map()
    {
        return ArrayHelper::map(static::find()->active()->asArray()->all(), 'id', 'username');
    }

    // ==== getters start ====

    /**
     * @return yii\rbac\Role[] 角色数组
     */
    public function getRoles()
    {
        return Yii::$app->authManager->getRolesByUser($this->id);
    }
    /**
     * @return string[] 角色名称数组
     */
    public function getRoleNames()
    {
        $names = [];
        $roles = $this->getRoles();

        if (!empty($roles)) {
            foreach ($roles as $role) {
                $names[] = $role->name;
            }
        }
        return $names;
    }

    /**
     * @return string
     */
    public function getReadableRole()
    {
        $slices = [];
        $roles = $this->getRoles();

        if (empty($roles)) {
            return '';
        }

        $colorMap = [
            'admin' => 'danger',
            'staff' => 'primary',
        ];
        foreach ($roles as $role) {
            $slices[] = Html::tag('span', $role->description, [
                'class' => 'label label-' . $colorMap[$role->name],
            ]);
        }

        return implode("&nbsp;", $slices);
    }


    /*
    public function getStatusLabel()
    {
        $map = [
            self::STATUS_ACTIVE => 'success',
            self::STATUS_ARCHIVED => 'default',
        ];
        $class = 'label label-' . $map[$this->status];
        return Html::tag('span', $this->lookup('Status', $this->status), ['class' => $class]);
    }
    */

    /**
     * 无需 sort 和 pagination 的 data provider
     *
    public function getItemsDataProvider()
    {
        return new ActiveDataProvider([
            'query' => static::find(),
            'pagination' => false,
            'sort' => false,
        ]);
    }
    */
    /**
     * 搭配 getItemsDataProvider() 使用，
     * 计算累计值，可用在 grid footer 内
    public function getItemsSum()
    {
        $amount = 0;

        if (empty($this->itemsDataProvider->models)) {
            return $amount;
        }
        foreach ($this->itemsDataProvider->models as $item) {
            $amount += $item->quantity;
        }

        return $amount;
        
    }
     */
    // ==== getters end ====

    /**
     * AJAX 提交表单逻辑代码
     *
    public static function ajaxSubmit($post)
    {
        $d['status'] = true;

        if (empty($post['Spu']['id'])) {
            $model = new Spu();
        } else {
            $model = Spu::findOne($post['Spu']['id']);
        }
        $model->load($post);

        // items
        $items = [];
        foreach ($post['PurchaseItem'] as $index => $item) {
            $items[$index] = new PurchaseItem();
        }
        PurchaseItem::loadMultiple($items, $post);
        foreach ($post['PurchaseItem'] as $index => $item) {
            $d['status'] = $items[$index]->validate() && $d['status'];
            if (!$items[$index]->validate()) {
                $key = "purchaseitem-$index";
                $d['errors'][$key] = $items[$index]->getErrors();
            }
        }

        // all data is safe, start to submit 
        if ($d['status']) {
            // 根据需要调整如 status 列值
            $model->on(self::EVENT_AFTER_INSERT, [$model, 'insertItems'], ['items' => $items]);

            $model->on(self::EVENT_BEFORE_UPDATE, [$model, 'deleteItems']);
            $model->on(self::EVENT_AFTER_UPDATE, [$model, 'insertItems'], ['items' => $items]);

            if (!$model->save()) {
                throw new \yii\db\Exception($model->stringifyErrors());
            }
            
            $d['message'] = Html::tag('span', Html::icon('check') . '已保存', [
                'class' => 'text-success',
            ]);
            $d['redirectUrl'] = Url::to(['/purchase/index']);
        }

        return $d;
    }
    */

    // ==== event-handlers begin ====

    /**
     * 保存附件。
     *
     * 可由 self::EVENT_AFTER_INSERT, self::EVENT_UPLOAD 等触发
     *
     * @param yii\web\UploadedFile $event->data 承兑图片
    public function insertImages($event)
    {
        $images = $event->data;

        Media::store([
            'files' => $images,
            'referenceId' => $this->id,
            'type' => Media::TYPE_IMAGE,
            'category' => Media::CATEGORY_ACCEPTANCE,
            'from2to' => Mapping::ACCEPTANCE2MEDIA,
        ]);
    }
     */

    /**
     * 删除文件
     *
     * 由 self::EVENT_BEFORE_DELETE 触发
    public function deleteImages($event)
    {
        foreach ($this->images as $image) {
            if (!$image->delete()) {
                throw new \yii\db\Exception('Failed to flush image.');
            }
        }
    }
     */
    // ==== event-handlers end ====
}
