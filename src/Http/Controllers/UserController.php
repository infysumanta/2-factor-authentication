<?php

namespace Sumantablog\LaravelAdmin\Google\GoogleAuthenticator\Http\Controllers;

use Sumantablog\Admin\Form;
use Sumantablog\Admin\Grid;
use Sumantablog\Admin\Show;
use Sumantablog\Admin\Controllers\AdminController;
use Sumantablog\LaravelAdmin\Google\GoogleAuthenticator\Core\PHPGangsta_GoogleAuthenticator;
use Sumantablog\LaravelAdmin\Google\GoogleAuthenticator\Models\Administrator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class UserController extends AdminController
{
    /**
     * {@inheritdoc}
     */
    protected function title()
    {
        return trans('admin.administrator');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $userModel = Administrator::class;

        $grid = new Grid(new $userModel());

        $grid->column('id', 'ID')->sortable();
        $grid->column('username', trans('admin.username'));
        $grid->column('name', trans('admin.name'));
        $grid->column('roles', trans('admin.roles'))->pluck('name')->label();
        $grid->column('created_at', trans('admin.created_at'));
        $grid->column('updated_at', trans('admin.updated_at'));

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            if ($actions->getKey() == 1) {
                $actions->disableDelete();
            }
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->batch(function (Grid\Tools\BatchActions $actions) {
                $actions->disableDelete();
            });
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        $userModel = Administrator::class;

        $show = new Show($userModel::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('username', trans('admin.username'));
        $show->field('name', trans('admin.name'));
        $show->field('roles', trans('admin.roles'))->as(function ($roles) {
            return $roles->pluck('name');
        })->label();
        $show->field('permissions', trans('admin.permissions'))->as(function ($permission) {
            return $permission->pluck('name');
        })->label();

        if (config('admin.extensions.laravel-admin-google-authenticator.enable', true)) {
            $show->field('google_auth', trans('admin.google_auth'));
            $row = $show->getModel();

            if (!is_null($row->google_auth)) {

                if (class_exists('QrCode')) {
                    $show->field('QrCode')->as(function () use ($row) {
                        $urlencoded = urlencode('otpauth://totp/' . $row->username . '?secret=' . $row->google_auth . '&issuer=' . urlencode(config('admin.name')));
                        return QrCode::size(200)->generate($urlencoded);
                    });
                }else{
                    $show->field('QrCode')->as(function () use ($row) {
                        $google = new PHPGangsta_GoogleAuthenticator();
                        return $google->getQRCodeGoogleUrl($row->username , $row->google_auth, $title = config('admin.name')) ;
                    })->image(); 
                }
                
            }

        }

        $show->field('created_at', trans('admin.created_at'));
        $show->field('updated_at', trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form()
    {
        $userModel = Administrator::class;

        $permissionModel = config('admin.database.permissions_model');
        $roleModel = config('admin.database.roles_model');

        $form = new Form(new $userModel());

        $userTable = config('admin.database.users_table');
        $connection = config('admin.database.connection');

        $form->display('id', 'ID');
        $form->text('username', trans('admin.username'))
            ->creationRules(['required', "unique:{$connection}.{$userTable}"])
            ->updateRules(['required', "unique:{$connection}.{$userTable},username,{{id}}"]);

        $form->text('name', trans('admin.name'))->rules('required');
        $form->image('avatar', trans('admin.avatar'));

        if($form->isEditing()) {
            if (!config('admin.extensions.laravel-admin-google-authenticator.enable', true)) {
                return;
            }

            $id = intval(request()->route()->parameters()['user']);
            $row = $userModel::find($id);

            if (!is_null($row->google_auth)) {

                $form->text('google_auth', __('google auth'));

                if (class_exists('QrCode')){
                    $urlencoded = urlencode('otpauth://totp/' . $row->username . '?secret=' . $row->google_auth .'&issuer=' . urlencode(config('admin.name')));
                    $form->html(QrCode::size(200)->generate($urlencoded));
                }else{
                    $google = new PHPGangsta_GoogleAuthenticator();
                    $form->html("<img src='{$google->getQRCodeGoogleUrl($row->username , $row->google_auth, $title = config('admin.name'))}' />");
                }

            }else{
                $form->hidden('google_auth');
            }

        }else{
            $form->hidden('google_auth');
        }

        $form->password('password', trans('admin.password'))->rules('required|confirmed');
        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });

        $form->ignore(['password_confirmation']);

        $form->multipleSelect('roles', trans('admin.roles'))->options($roleModel::all()->pluck('name', 'id'));
        $form->multipleSelect('permissions', trans('admin.permissions'))->options($permissionModel::all()->pluck('name', 'id'));

        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));

        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = bcrypt($form->password);
            }
        });

        return $form;
    }
}