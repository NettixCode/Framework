<?php

namespace Nettixcode\Framework\Libraries;

use Illuminate\Support\Facades\DB;
use Nettixcode\Framework\Facades\User;
use Nettixcode\Framework\Libraries\SessionManager;
use Illuminate\Http\JsonResponse;

class AuthManager
{
    public function id(){
        if (SessionManager::get('id')){
            return SessionManager::get('id');
        } else {
            return null;
        }
    }

    public function login($data, $dataSession)
    {
        $username = $data['username'];
        $password = $data['password'];

        $user = User::where('username', $username)->first();

        if ($user) {
            if (password_verify($password, $user->password)) {
                if ($user->status == 1) {
                    foreach ($user->toArray() as $key => $value) {
                        if ($key != 'password') {
                            SessionManager::set($key, $value);
                        }
                    }
                    foreach ($dataSession as $key => $value) {
                        SessionManager::set($key, $value);
                    }
                    SessionManager::set('user_role', User::role($user->id));
                    SessionManager::set('isLogin', true);

                    return new class ($user) {
                        private $user;
                        private $checkComplete = false;

                        public function __construct($user)
                        {
                            $this->user = $user;
                        }

                        public function checkComplete()
                        {
                            $this->checkComplete = true;
                            return $this;
                        }

                        public function __destruct()
                        {
                            if ($this->checkComplete) {
                                $incomplete = false;
                                foreach ($this->user->toArray() as $key => $value) {
                                    if (empty($value) && $key != 'password') {
                                        $incomplete = true;
                                    }
                                }

                                SessionManager::set('incomplete', $incomplete);

                                if ($incomplete) {
                                    return response()->json([
                                        'report'             => 'success',
                                        'incomplete'         => true,
                                        'incomplete_message' => 'Please Complete Your Profile.',
                                        'message'            => 'Login Success',
                                    ]);
                                } else {
                                    return response()->json([
                                        'report'     => 'success',
                                        'incomplete' => false,
                                        'message'    => 'Login Success',
                                    ]);
                                }
                            } else {
                                return response()->json([
                                    'report'  => 'success',
                                    'message' => 'Login Success',
                                ]);
                            }
                        }
                    };
                } else {
                    return response()->json([
                        'report'  => 'error',
                        'message' => 'Your Account is Inactive. Contact Administrator to Activate. Thanks. :)',
                    ]);
                }
            } else {
                return response()->json([
                    'report'  => 'error',
                    'message' => 'Wrong Password!',
                ]);
            }
        } else {
            return response()->json([
                'report'  => 'error',
                'message' => 'Username Not Found!',
            ]);
        }
    }

    public function logout()
    {
        SessionManager::destroy();

        return new class () {
            private $redirectUrl = '/';

            public function redirect($url = '/')
            {
                $this->redirectUrl = $url;

                return $this;
            }

            public function __destruct()
            {
                if ($this->redirectUrl) {
                    header("Location: $this->redirectUrl");
                    exit();
                }
            }
        };
    }

    public function password($data)
    {
        return new class ($data) {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function change()
            {
                $userId      = $this->data['id'];
                $oldPassword = $this->data['old_password'] ?? null;
                $newPassword = $this->data['new_password'];

                if (!$this->password_valid($newPassword)) {
                    return response()->json(['report' => 'error', 'message' => 'Password does not meet the requirements.']);
                }

                $user = User::where('id', $userId)->first();
                if (!$user) {
                    return response()->json(['report' => 'error', 'message' => 'User not found.']);
                }

                $newPassHash = password_hash($newPassword, PASSWORD_BCRYPT);

                if ($this->check_roles($userId)) {
                    return $this->handle_checked_roles($user, $oldPassword, $newPassword, $newPassHash);
                } elseif (User::has('role', 'admin') && User::role($userId) != 1) {
                    return $this->update_password($userId, $newPassHash);
                } else {
                    return response()->json(['report' => 'error', 'message' => 'Unauthorized action.']);
                }
            }

            private function check_roles($userId)
            {
                return (User::has('role', 'admin') && User::role($userId) == 1) || User::has('role', 'user');
            }

            private function handle_checked_roles($user, $oldPassword, $newPassword, $newPassHash)
            {
                if (password_verify($oldPassword, $user->password)) {
                    if ($oldPassword != $newPassword) {
                        return $this->update_password($user->id, $newPassHash);
                    } else {
                        return response()->json(['report' => 'error', 'message' => 'Password tidak boleh sama dengan yang lama!']);
                    }
                } else {
                    return response()->json(['report' => 'error', 'message' => 'Silahkan input user_pass lama anda untuk mengubah user_pass. Terima kasih. :)']);
                }
            }

            private function update_password($userId, $newPassHash)
            {
                $result = User::where('id', $userId)->update(['password' => $newPassHash]);

                return $this->generateResponse($result, $userId);
            }

            private function generateResponse($result, $userId)
            {
                if ($result) {
                    SessionManager::get('id') == $userId ? SessionManager::set('isLogin', false) : SessionManager::set('isLogin', true);

                    return response()->json(['report' => 'success', 'id' => $userId]);
                } else {
                    return response()->json(['report' => 'error', 'message' => 'Failed to update password.']);
                }
            }

            private function password_valid($password)
            {
                // Contoh validasi: panjang minimal 8 karakter, harus mengandung huruf besar, huruf kecil, angka, dan karakter spesial
                return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
            }
        };
    }
}
