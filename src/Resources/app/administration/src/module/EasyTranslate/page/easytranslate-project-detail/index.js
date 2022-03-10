import template from './easytranslate-project-detail.html.twig';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('easytranslate-project-detail', {
    template,

    inject: [
        'easyTranslateApiService',
        'repositoryFactory',
        'acl',
    ],

    mixins: [
        'notification',
        'placeholder',
    ],

    shortcuts: {
        'SYSTEMKEY+S': {
            active() {
                return this.acl.can('project.editor');
            },
            method: 'onSave',
        },
        ESCAPE: 'onCancel',
    },

    props: {
        projectId: {
            type: String,
            required: false,
            default() {
                return null;
            },
        },
    },

    data() {
        return {
            isLoading: false,
            project: null,
            isSaveSuccessful: false,
            isSendSuccessful: false,
            isSendPriceSuccessful: false,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.identifier),
        };
    },

    computed: {
        identifier() {
            return this.placeholder(this.project, 'name');
        },

        isCreateMode() {
            return this.$route.name === 'easytranslate.project.create.base';
        },

        projectRepository() {
            return this.repositoryFactory.create('easytranslate_project');
        },

        projectCriteria() {
            const criteria = new Criteria(1, 1);

            criteria
                .addAssociation('sourceLanguage')
                .addAssociation('categories')
                .addAssociation('products')
                .addAssociation('targetLanguages');

            return criteria;
        },

        // TODO: Figure out if locale translation could be shown instead of original language name
        targetLanguagesCriteria() {
            const criteria = new Criteria();

            criteria.addFilter(Criteria.not('AND', [Criteria.equals('id', this.project.sourceLanguageId)]));

            return criteria;
        },

        categoryAndProductCriteria() {
            const criteria = new Criteria();

            // TODO: If existing project, exclude target languages, where the project doesn't match source language
            criteria
                .addAssociation('easyTranslateProjects.targetLanguages');

            if (this.projectId) {
                return criteria;
            }

            criteria
                .addAssociation('translations')
                .addFilter(Criteria.equals('translations.languageId', this.project.sourceLanguageId));

            if (this.project.targetLanguages.getIds().length > 0) {
                criteria.addFilter(Criteria.not('AND', [
                    Criteria.equalsAny('easyTranslateProjects.targetLanguages.id', this.project.targetLanguages.getIds())
                ]));
            }

            return criteria;
        },

        categoryColumns() {
            return [{
                property: 'name',
                label: 'easytranslate-project.detail.base.categories.columnName',
                routerLink: 'sw.category.detail',
            }, {
                property: 'type',
                label: 'easytranslate-project.detail.base.categories.columnType',
            }, {
                property: 'easyTranslateProjects.targetLanguages',
                label: 'easytranslate-project.detail.base.categories.columnTargetLanguages',
            }];
        },

        productColumns() {
            return [{
                property: 'name',
                label: 'easytranslate-project.detail.base.products.columnName',
                routerLink: 'sw.product.detail',
            }, {
                property: 'productNumber',
                label: 'easytranslate-project.detail.base.products.columnProductNumber',
            }, {
                property: 'easyTranslateProjects.targetLanguages',
                label: 'easytranslate-project.detail.base.products.columnTargetLanguages',
            }];
        },

        showPriceActions() {
            if (this.project) {
                return this.project.status === 'APPROVAL_NEEDED'; // TODO: Have some kind of constant for this
            }
        },

        showSendAction() {
            if (this.project) {
                return this.project.status === 'INIT'; // TODO: Have some kind of constant for this
            }
        },

        tooltipSave() {
            if (!this.acl.can('project.editor')) {
                return {
                    message: this.$tc('sw-privileges.tooltip.warning'),
                    disabled: this.acl.can('category.editor'),
                    showOnDisabledElements: true,
                };
            }

            const systemKey = this.$device.getSystemKey();

            return {
                message: `${systemKey} + S`,
                appearance: 'light',
            };
        },

        tooltipCancel() {
            return {
                message: 'ESC',
                appearance: 'light',
            };
        },
    },

    watch: {
        projectId() {
            this.createdComponent();
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isLoading = true;

            if (!this.projectId) {
                this.project = this.projectRepository.create();
                this.project.status = 'INIT';

                this.isLoading = false;

                return;
            }

            this.loadEntityData();
        },

        loadEntityData() {
            return this.projectRepository.get(this.projectId, Context.api, this.projectCriteria)
                .then((project) => {
                    if (project === null) {
                        return;
                    }

                    this.project = project;
                }).catch((error) => {
                    this.createNotificationError(error);
                }).finally(() => {
                    this.isLoading = false;
                });
        },

        onSend() {
            if (this.projectId) {
                this.isLoading = true;

                this.easyTranslateApiService.sendProject({
                    projectId: this.projectId
                }).then(() => {
                    this.createNotificationSuccess({
                        message: this.$tc('easytranslate-project.detail.notification.sendProjectSuccess'),
                    });
                    this.$router.push({ name: 'easytranslate.project.index' });
                }).catch(() => {
                    this.createNotificationError({
                        message: this.$tc('easytranslate-project.detail.notification.sendProjectError'),
                    });
                    this.isLoading = false;
                });
            }
        },

        sendFinish() {
            this.isSendSuccessful = false;
        },

        onDeclinePrice() {
            this.onSendPrice('DECLINED');
        },

        onAcceptPrice() {
            this.onSendPrice('APPROVED');
        },

        onSendPrice(action) {
            if (this.projectId) {
                this.isLoading = true;

                this.easyTranslateApiService.handlePrice({
                    projectId: this.projectId,
                    action: action
                }).then(() => {
                    this.createNotificationSuccess({
                        message: this.$tc('easytranslate-project.detail.notification.sendPriceSuccess'),
                    });
                    this.$router.push({ name: 'easytranslate.project.index' });
                }).catch(() => {
                    this.createNotificationError({
                        message: this.$tc('easytranslate-project.detail.notification.sendPriceError'),
                    });
                    this.isLoading = false;
                });
            }
        },

        sendPriceFinish() {
            this.isSendPriceSuccessful = false;
        },

        onSave() {
            if (!this.projectId) {
                if (this.project.categories.length === 0 && this.project.products.length === 0) {
                    this.createNotificationError({
                        message: this.$tc('easytranslate-project.detail.notification.noCategoriesAndProductsSaveError'),
                    });
                    return;
                }
                this.createProject();
            }
        },

        createProject() {
            return this.saveProject().then(() => {
                if (!this.isSaveSuccessful) return;
                this.$router.push({ name: 'easytranslate.project.detail', params: { id: this.project.id } });
            }).catch((error) => {
                this.createNotificationError(error);
            });
        },

        saveProject() {
            this.isLoading = true;

            return this.projectRepository.save(this.project)
                .then(() => {
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        message: this.$tc('global.notification.notificationSaveSuccessMessage', 0, {
                            entityName: this.project.name,
                        }),
                    });

                    this.loadEntityData();
                })
                .catch(() => {
                    this.isLoading = false;
                    this.createNotificationError({
                        message: this.$tc('global.notification.notificationSaveErrorMessage', 0, {
                            entityName: this.project.name,
                        }),
                    });
                });
        },

        saveFinish() {
            this.isSaveSuccessful = false;
        },

        onCancel() {
            this.$router.push({ name: 'easytranslate.project.index' });
        },
    },
});
