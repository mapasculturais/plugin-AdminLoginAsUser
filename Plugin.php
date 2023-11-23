<?php
namespace AdminLoginAsUser;

use MapasCulturais\App;
use MapasCulturais\Exceptions\PermissionDenied;
use MapasCulturais\i;
use MapasCulturais\Plugin as MapasCulturaisPlugin;

class Plugin extends MapasCulturaisPlugin {
    function _init()
    {
        $app = App::i();

        // adiciona o menu "Voltar como administrador"
        $app->hook('panel.nav', function (&$group) use ($app) {
            if (isset($_SESSION['auth.asUserId'])) {
                $group['more']['items'][] = [
                    'route' => 'auth/asUserId',
                    'icon' => 'user-config',
                    'label' => i::__('Voltar como administrador')
                ];
            }
        });

        // adiciona o botão "logar" na listagem da gestão de usuários
        $app->hook('component(panel--card-user).actions-right:begin', function () {
            $this->part('admin-login-as-user--link');
        });

        // substitui o $app->user pelo o usuário selecionado
        $app->hook('App.get(user)', function(&$current_user) use($app) {
            if($current_user->is('admin') && isset($_SESSION['auth.asUserId'])) {
                $app = App::i();
                
                $user = $app->repo('User')->find($_SESSION['auth.asUserId']);

                if (!$current_user->is('saasSuperAdmin') && $user->is('saasSuperAdmin') ||
                    !$current_user->is('saasAdmin') && $user->is('saasAdmin') ||
                    !$current_user->is('superAdmin') && $user->is('superAdmin')) {
                        throw new PermissionDenied($current_user, $user, i::__('trocar usuário'));
                }

                $current_user = $user;
            }
        });

        // rota que define o usuário selecionado
        $app->hook('GET(auth.asUserId)', function () {
            /** @var Auth $this */
            $app = App::i();

            $finish = function () use($app) {
                if ($app->request->isAjax()) {
                    $this->json(true);
                } else {
                    $app->redirect($app->createUrl('panel', 'index'));
                }
            };
            
            $this->requireAuthentication();
            unset($_SESSION['auth.asUserId']);
            
            $current_user = $app->user;
            if (!$current_user->is('admin')) {
                $this->errorJson(i::__('Permissão negada'), 403);
            }

            $as_user_id = $this->data['id'] ?? false;

            // se não foi enviado user_id, volta como administrador
            if (!$as_user_id) {
                $finish();
            }

            $user = $app->repo('User')->find($as_user_id);

            // se não foi achou o usuário do user_id, volta como administrador
            if(!$user) {
                $finish();
            }

            if (!$current_user->is('saasSuperAdmin') && $user->is('saasSuperAdmin') ||
                !$current_user->is('saasAdmin') && $user->is('saasAdmin') ||
                !$current_user->is('superAdmin') && $user->is('superAdmin')) {
                    $this->errorJson(i::__('Permissão negada: Você não pode assumir um perfil com permissão superior a sua'), 403);
            }

            $_SESSION['auth.asUserId'] = $as_user_id;
    
            $finish();
        });
    }

    function register() {}
}
