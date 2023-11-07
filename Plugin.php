<?php
namespace AdminLoginAsUser;

use MapasCulturais\App;
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
        $app->hook('App.get(user)', function(&$user) use($app) {
            if($user->is('saasSuperAdmin') && isset($_SESSION['auth.asUserId'])) {
                $app = App::i();
                
                $user = $app->repo('User')->find($_SESSION['auth.asUserId']);
            }
        });

        // rota que define o usuário selecionado
        $app->hook('GET(auth.asUserId)', function () {
            /** @var Auth $this */
            $app = App::i();
            
            $this->requireAuthentication();
            unset($_SESSION['auth.asUserId']);
    
            if (!$app->user->is('saasSuperAdmin')) {
                $this->errorJson(i::__('Permissão negada'), 403);
            }
            
            if (($as_user_id = $this->data['id'] ?? false) && ($user = $app->repo('User')->find($as_user_id))) {
                $_SESSION['auth.asUserId'] = $as_user_id;
            }
    
            if ($app->request->isAjax()) {
                $this->json(true);
            } else {
                $app->redirect($app->createUrl('panel', 'index'));
            }
        });
    }

    function register() {}
}
