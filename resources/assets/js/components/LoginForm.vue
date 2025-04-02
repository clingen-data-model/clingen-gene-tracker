<script setup>
import { ref } from 'vue'


const email = ref('')
const password = ref('')
const remember = ref(false)
const errors = ref({})

</script>
<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-default">
                    <div class="card-header">Login</div>

                    <div class="card-body">
                        <form method="POST" action="/api/login">

                            <div class="form-group row">
                                <label for="email" class="col-md-4 col-form-label text-md-right">E-Mail Address</label>

                                <div class="col-md-6">
                                    <input id="email" type="email" v-model="email" name="email"
                                        v-bind:class="`form-control ${errors.email ? 'is-invalid' : ''}`"
                                        required autocomplete="email" autofocus>

                                    <span v-if="errors['email']" class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="password" class="col-md-4 col-form-label text-md-right">Password</label>

                                <div class="col-md-6">
                                    <input id="password" type="password" name="password" v-model="password"
                                        v-bind:class="`form-control ${errors.password ? 'is-invalid' : ''}`"
                                        required autocomplete="current-password">

                                    <span v-if="errors['password']" class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6 offset-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" v-model="remember"
                                            type="checkbox" name="remember" id="remember" />

                                        <label class="form-check-label" for="remember">
                                            Remember Me
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-8 offset-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        Login
                                    </button>

                                    <a v-if="routeHasPasswordRequest" class="btn btn-link" href="/FIXME/password-reset">
                                        Forgot Your Password?
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>