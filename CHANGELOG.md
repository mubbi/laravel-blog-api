# Changelog

## [2.1.0](https://github.com/mubbi/laravel-blog-api/compare/v2.0.0...v2.1.0) (2026-01-01)


### Features

* **events:** added events and listeners ([52d8fe1](https://github.com/mubbi/laravel-blog-api/commit/52d8fe1d26512b013915c09c67d1dc6aef886e27))


### Bug Fixes

* fixed N+1 query problem ([c62b357](https://github.com/mubbi/laravel-blog-api/commit/c62b3573b28c31f2898b8a99f5b60900d54fa9a3))

## [2.0.0](https://github.com/mubbi/laravel-blog-api/compare/v1.10.0...v2.0.0) (2025-12-31)


### ⚠ BREAKING CHANGES

* **updated dependencies:** updated packages for composer and npm packages

### Build System

* **updated dependencies:** updated packages for composer and npm packages ([1e6e36f](https://github.com/mubbi/laravel-blog-api/commit/1e6e36ff0efcb7f29e7c737f22167cb34973dcac))

## [1.10.0](https://github.com/mubbi/laravel-blog-api/compare/v1.9.0...v1.10.0) (2025-08-05)


### Features

* add more permissions ([e5a8844](https://github.com/mubbi/laravel-blog-api/commit/e5a88445a0d394b0098140ce5a7edfbab9b008ed))

## [1.9.0](https://github.com/mubbi/laravel-blog-api/compare/v1.8.1...v1.9.0) (2025-08-05)


### Features

* **api:** implemented missing modules ([43314ab](https://github.com/mubbi/laravel-blog-api/commit/43314abd370c7c44802152b3e8b6e07fd26a9e9d))

## [1.8.1](https://github.com/mubbi/laravel-blog-api/compare/v1.8.0...v1.8.1) (2025-07-30)


### Bug Fixes

* **phpstan:** removed ignore rules and applied type strict stubs ([19f44a0](https://github.com/mubbi/laravel-blog-api/commit/19f44a09d682a2bd7e14c9c8f06eb7c92477d399))

## [1.8.0](https://github.com/mubbi/laravel-blog-api/compare/v1.7.1...v1.8.0) (2025-07-29)


### Features

* **articles-seeder:** added multiple co authors seeding ([985bb50](https://github.com/mubbi/laravel-blog-api/commit/985bb501e49f24da9df5e04fafee4c0ff1771c77))
* **article:** show authors and restrict email field to admins ([77acaf6](https://github.com/mubbi/laravel-blog-api/commit/77acaf6bafad2b2a1c0caec70a0ed14f0a7ba8f5))
* **middleware:** added new middleware for public routes ([68f8d90](https://github.com/mubbi/laravel-blog-api/commit/68f8d905296e9861484c446059eba3dcef13d0c0))
* **middleware:** apply optional sanctum auth middleware for guest routes ([fe37480](https://github.com/mubbi/laravel-blog-api/commit/fe37480cebb530189653029011b710aedb0d5a63))

## [1.7.1](https://github.com/mubbi/laravel-blog-api/compare/v1.7.0...v1.7.1) (2025-07-28)


### Bug Fixes

* **comments:** fixed N+1 problem in comments replies listing ([2650fe2](https://github.com/mubbi/laravel-blog-api/commit/2650fe28aef8661655779c395cf08e2c95cbc1d9))
* **comments:** fixed N+1 problem in comments replies listing ([f7bde1b](https://github.com/mubbi/laravel-blog-api/commit/f7bde1b02ff2da55cf21d36926cb28174cd215ed))

## [1.7.0](https://github.com/mubbi/laravel-blog-api/compare/v1.6.0...v1.7.0) (2025-07-27)


### Features

* **comments:** added Get Article comments API ([322cce3](https://github.com/mubbi/laravel-blog-api/commit/322cce35935bb03037446f27bae4bc71c2013e6b))
* **comments:** added Get Article comments API ([fbff564](https://github.com/mubbi/laravel-blog-api/commit/fbff5647394d7c31bbd5beab4f02e433543cca9a))

## [1.6.0](https://github.com/mubbi/laravel-blog-api/compare/v1.5.0...v1.6.0) (2025-07-24)


### Features

* **api logger:** added api logger to track requests and responses ([703b6f1](https://github.com/mubbi/laravel-blog-api/commit/703b6f16c7beb0f0b66a17fed2d7ff94deadf953))
* **api logger:** added api logger to track requests and responses ([ef63e33](https://github.com/mubbi/laravel-blog-api/commit/ef63e33e7d58ef533d2cf0e77014f360f1a1c0c3))

## [1.5.0](https://github.com/mubbi/laravel-blog-api/compare/v1.4.0...v1.5.0) (2025-07-24)


### Features

* **categories & tags:** added New APIs for categories and tags ([44347d5](https://github.com/mubbi/laravel-blog-api/commit/44347d548373d0d1a8d599dae9de00a0ac4d3b24))
* **categories & tags:** added New APIs for categories and tags ([6b53386](https://github.com/mubbi/laravel-blog-api/commit/6b53386903023fd120310733e49d5bcd09d4ef58))

## [1.4.0](https://github.com/mubbi/laravel-blog-api/compare/v1.3.1...v1.4.0) (2025-07-23)


### Features

* **rate limiter:** applied rate limiter for APIs ([bb9226c](https://github.com/mubbi/laravel-blog-api/commit/bb9226cbe1fcea0bb48d8fba9987201f271cad27))
* **rate limiter:** applied rate limiter for APIs ([795cbff](https://github.com/mubbi/laravel-blog-api/commit/795cbffc9955e025689cf0eb997410105e920635))

## [1.3.1](https://github.com/mubbi/laravel-blog-api/compare/v1.3.0...v1.3.1) (2025-07-23)


### Bug Fixes

* **article:** fix API Responses and paths ([0de982a](https://github.com/mubbi/laravel-blog-api/commit/0de982a8019e0e03e3b4054bbbedf7334cd7e3f0))
* **article:** fix API Responses and paths ([a359ec4](https://github.com/mubbi/laravel-blog-api/commit/a359ec4220a6898784f210be63aacd5ddcf61b8c))

## [1.3.0](https://github.com/mubbi/laravel-blog-api/compare/v1.2.3...v1.3.0) (2025-07-22)


### Features

* add test coverage for me controller ([c8549c9](https://github.com/mubbi/laravel-blog-api/commit/c8549c9106f3a5d54d34e7fba8b9b6a8c47b9966))
* added Me controller for get user profile API ([29dc842](https://github.com/mubbi/laravel-blog-api/commit/29dc842af648c5dbd7c18467ecf9ec12f5d6ab96))
* **article:** add all author related fields ([644b63a](https://github.com/mubbi/laravel-blog-api/commit/644b63a0993e3b406de5c64c56682e60910c0cb6))
* **article:** added Get All and Show Article APIs ([3d8ea96](https://github.com/mubbi/laravel-blog-api/commit/3d8ea965a96fa36993384519693c7dfe21ccf797))
* articles API ([dadb3ff](https://github.com/mubbi/laravel-blog-api/commit/dadb3ff6d4442f01619a2abd83509b7f95976acc))
* **article:** update models to have respective relationships ([8fbb984](https://github.com/mubbi/laravel-blog-api/commit/8fbb984d89c3780942b1eebe90d68737ed909ff3))


### Bug Fixes

* fix user resource for undefined key access_token ([8ea86b8](https://github.com/mubbi/laravel-blog-api/commit/8ea86b8ef2766fd45cea9c4c0112e38fc112948f))

## [1.2.3](https://github.com/mubbi/laravel-blog-api/compare/v1.2.2...v1.2.3) (2025-07-19)


### Bug Fixes

* fix test scripts to persist container ([293d12d](https://github.com/mubbi/laravel-blog-api/commit/293d12d3d3b0595d6a5f984a47c93bd599fb179b))
* fixed port check issues ([d59abca](https://github.com/mubbi/laravel-blog-api/commit/d59abcaa3baf5332c0ba96652aa0b6c1316d7768))

## [1.2.2](https://github.com/mubbi/laravel-blog-api/compare/v1.2.1...v1.2.2) (2025-07-18)


### Bug Fixes

* fix port check commands and git hook setup ([dc7493b](https://github.com/mubbi/laravel-blog-api/commit/dc7493b8b6314442fe425bf0f5c47bbd508d0efd))

## [1.2.1](https://github.com/mubbi/laravel-blog-api/compare/v1.2.0...v1.2.1) (2025-07-18)


### Bug Fixes

* fix health check for queue worker to get its correct status ([ec6e20d](https://github.com/mubbi/laravel-blog-api/commit/ec6e20d83ea0656773eb058ff63db8f44d0ddbfb))
* fix health check for queue worker to get its correct status ([0f3ead1](https://github.com/mubbi/laravel-blog-api/commit/0f3ead1d053f5d0fee469128b830123f76e8e5d7))

## [1.2.0](https://github.com/mubbi/laravel-blog-api/compare/v1.1.0...v1.2.0) (2025-07-18)


### Features

* added port checker warning for local setup ([ad56a38](https://github.com/mubbi/laravel-blog-api/commit/ad56a3895dda02cf393fc06d87f74ef1fe3f4a3d))
* added port checker warning for local setup ([00522a0](https://github.com/mubbi/laravel-blog-api/commit/00522a0655c9e7168325806f75e68412177abdd1))

## [1.1.0](https://github.com/mubbi/laravel-blog-api/compare/v1.0.1...v1.1.0) (2025-07-18)


### Features

* updated project instructions for copilot ([5dc94e5](https://github.com/mubbi/laravel-blog-api/commit/5dc94e5e5810146509ec6b1f2b485dd6facf9d90))
* updated project instructions for copilot ([fffdc9b](https://github.com/mubbi/laravel-blog-api/commit/fffdc9ba6237d259b99fe5a9b6ded15686ca1b02))

## [1.0.1](https://github.com/mubbi/laravel-blog-api/compare/v1.0.0...v1.0.1) (2025-07-18)


### Bug Fixes

* **git hooks:** fixed git hooks for husky ([3b165be](https://github.com/mubbi/laravel-blog-api/commit/3b165be1eb20a1dcfcace339ac1de8708287d029))

## 1.0.0 (2025-07-18)


### ⚠ BREAKING CHANGES

* All commits must now follow conventional commit format

### Features

* implement semantic commit automation with commitizen, commitlint, and release-please ([711b9d1](https://github.com/mubbi/laravel-blog-api/commit/711b9d13b33e570fd72f1fe38f85c89b8a24f9a2))


### Bug Fixes

* **fix workflow:** fixed workflow for release phase ([44163cb](https://github.com/mubbi/laravel-blog-api/commit/44163cb4b2a63ee184feacadadc6ee37834f217b))
