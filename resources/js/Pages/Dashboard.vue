<script setup>
import { Head } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-linear-to-br from-primary-50 to-accent-50 flex items-center justify-center p-4">
        <Card class="w-full max-w-4xl shadow-2xl">
            <template #header>
                <div class="bg-linear-to-r from-primary-600 to-primary-800 p-8 text-center">
                    <h1 class="text-4xl font-bold text-white mb-2">
                        Learning Management System
                    </h1>
                    <p class="text-primary-100">
                        Empower your learning journey
                    </p>
                </div>
            </template>

            <template #content>
                <div class="text-center space-y-6">
                    <p class="text-lg text-darker-700">
                        Welcome to our comprehensive Learning Management System. Create courses, track progress, and engage with discussions.
                    </p>

                    <div v-if="canLogin" class="flex gap-4 justify-center">
                        <Button
                            label="Log in"
                            icon="pi pi-sign-in"
                            severity="primary"
                            size="large"
                            as="a"
                            href="/login"
                        />
                        <Button
                            v-if="canRegister"
                            label="Register"
                            icon="pi pi-user-plus"
                            severity="secondary"
                            size="large"
                            outlined
                            as="a"
                            href="/register"
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                        <div class="text-center p-4">
                            <i class="pi pi-book text-4xl text-primary-600 mb-3"></i>
                            <h3 class="font-semibold text-darker-800 mb-2">Create Courses</h3>
                            <p class="text-sm text-darker-600">Instructors can create comprehensive courses with multiple pages</p>
                        </div>
                        <div class="text-center p-4">
                            <i class="pi pi-chart-line text-4xl text-primary-600 mb-3"></i>
                            <h3 class="font-semibold text-darker-800 mb-2">Track Progress</h3>
                            <p class="text-sm text-darker-600">Monitor student progress through course materials</p>
                        </div>
                        <div class="text-center p-4">
                            <i class="pi pi-comments text-4xl text-primary-600 mb-3"></i>
                            <h3 class="font-semibold text-darker-800 mb-2">Engage & Discuss</h3>
                            <p class="text-sm text-darker-600">Participate in course discussions and collaborate</p>
                        </div>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
