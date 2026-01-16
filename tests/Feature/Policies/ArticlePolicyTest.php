<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\User;
use App\Policies\ArticlePolicy;

describe('ArticlePolicy', function () {
    beforeEach(function () {
        $this->policy = new ArticlePolicy;
    });

    describe('view', function () {
        it('allows user with view_posts permission to view article', function () {
            $user = createUserWithPermission('view_posts');
            $article = Article::factory()->create();

            expect($this->policy->view($user, $article))->toBeTrue();
        });

        it('denies user without view_posts permission to view article', function () {
            $user = User::factory()->create();
            $article = Article::factory()->create();

            expect($this->policy->view($user, $article))->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows user with publish_posts permission to create article', function () {
            $user = createUserWithPermission('publish_posts');

            expect($this->policy->create($user))->toBeTrue();
        });

        it('denies user without publish_posts permission to create article', function () {
            $user = User::factory()->create();

            expect($this->policy->create($user))->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows user with edit_others_posts permission to update any article', function () {
            $user = createUserWithPermission('edit_others_posts');
            $otherUser = User::factory()->create();
            $article = Article::factory()->create(['created_by' => $otherUser->id]);

            expect($this->policy->update($user, $article))->toBeTrue();
        });

        it('allows user with edit_posts permission to update own article', function () {
            $user = createUserWithPermission('edit_posts');
            $article = Article::factory()->create(['created_by' => $user->id]);

            expect($this->policy->update($user, $article))->toBeTrue();
        });

        it('denies user with edit_posts permission to update other users article', function () {
            $user = createUserWithPermission('edit_posts');
            $otherUser = User::factory()->create();
            $article = Article::factory()->create(['created_by' => $otherUser->id]);

            expect($this->policy->update($user, $article))->toBeFalse();
        });

        it('denies user without edit permissions to update article', function () {
            $user = User::factory()->create();
            $article = Article::factory()->create(['created_by' => $user->id]);

            expect($this->policy->update($user, $article))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows user with delete_others_posts permission to delete any article', function () {
            $user = createUserWithPermission('delete_others_posts');
            $otherUser = User::factory()->create();
            $article = Article::factory()->create(['created_by' => $otherUser->id]);

            expect($this->policy->delete($user, $article))->toBeTrue();
        });

        it('allows user with delete_posts permission to delete own article', function () {
            $user = createUserWithPermission('delete_posts');
            $article = Article::factory()->create(['created_by' => $user->id]);

            expect($this->policy->delete($user, $article))->toBeTrue();
        });

        it('denies user with delete_posts permission to delete other users article', function () {
            $user = createUserWithPermission('delete_posts');
            $otherUser = User::factory()->create();
            $article = Article::factory()->create(['created_by' => $otherUser->id]);

            expect($this->policy->delete($user, $article))->toBeFalse();
        });

        it('denies user without delete permissions to delete article', function () {
            $user = User::factory()->create();
            $article = Article::factory()->create(['created_by' => $user->id]);

            expect($this->policy->delete($user, $article))->toBeFalse();
        });
    });
});
