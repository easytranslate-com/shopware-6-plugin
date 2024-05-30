import template from './easytranslate-project-detail.html.twig';

const { Component, Context } = Shopware;
const { mapPropertyErrors } = Shopware.Component.getComponentHelper();
const { Criteria } = Shopware.Data;
const ShopwareError = Shopware.Classes.ShopwareError;

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
            workflowOptions: [],
            nameIsEmpty: false,
            workflowIsEmpty: false,
            sourceLanguageIsEmpty: false,
            targetLanguagesIsEmpty: false,
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
        ...mapPropertyErrors('project', [
            'name',
            'workflow',
        ]),

        // Needed for check that happens same time as source and target check
        nameError() {
            if (this.nameIsEmpty) {
                return new ShopwareError({
                    code: 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                });
            }
            return null;
        },

        // Needed for check that happens same time as source and target check
        workflowError() {
            if (this.workflowIsEmpty) {
                return new ShopwareError({
                    code: 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                });
            }
            return null;
        },

        sourceLanguageError() {
            if (this.sourceLanguageIsEmpty) {
                return new ShopwareError({
                    code: 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                });
            }
            return null;
        },

        targetLanguagesError() {
            if (this.targetLanguagesIsEmpty) {
                return new ShopwareError({
                    code: 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                });
            }
            return null;
        },

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

        // TODO: Future improvement. Figure out if locale translation could be shown instead of original language name
        targetLanguagesCriteria() {
            const criteria = new Criteria();

            criteria.addFilter(Criteria.not('AND', [Criteria.equals('id', this.project.sourceLanguageId)]));

            return criteria;
        },

        categoryCriteria() {
            const criteria = new Criteria();

            criteria
                .addAssociation('easyTranslateProjects.targetLanguages');

            if (this.projectId) {
                return criteria;
            }

            criteria
                .addAssociation('translations')
                .addFilter(Criteria.equals('translations.languageId', this.project.sourceLanguageId));

            criteria.addFilter(Criteria.not('AND', [
                Criteria.equalsAny('easyTranslateProjects.targetLanguages.id', this.project.targetLanguages.getIds())
            ]));

            return criteria;
        },

        productCriteria() {
            const criteria = new Criteria();

            if (this.projectId) {
                return criteria;
            }

            return criteria;
        },

        categoryColumns() {
            return [{
                property: 'name',
                label: this.$tc('easytranslate-project.detail.base.categories.columnName'),
                routerLink: 'sw.category.detail',
            }, {
                property: 'type',
                label: this.$tc('easytranslate-project.detail.base.categories.columnType'),
            }, {
                property: 'easyTranslateProjects.targetLanguages',
                label: this.$tc('easytranslate-project.detail.base.categories.columnTargetLanguages'),
                sortable: false,
            }];
        },

        productColumns() {
            return [{
                property: 'name',
                label: this.$tc('easytranslate-project.detail.base.products.columnName'),
                routerLink: 'sw.product.detail',
            }, {
                property: 'productNumber',
                label: this.$tc('easytranslate-project.detail.base.products.columnProductNumber'),
            }, {
                property: 'easyTranslateProjects.targetLanguages',
                label: this.$tc('easytranslate-project.detail.base.products.columnTargetLanguages'),
                sortable: false,
            }];
        },

        showPriceActions() {
            if (this.project) {
                return this.project.status === 'APPROVAL_NEEDED';
            }
        },

        showSendAction() {
            if (this.project) {
                return this.project.status === 'INIT';
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
            } else {
                this.loadEntityData();
            }

            this.easyTranslateApiService.getWorkflowOptions()
                .then((options) => {
                    this.workflowOptions = options;
                })
                .catch((error) => {
                    this.createNotificationsError(error);
                })
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
                }).catch((error) => {
                    let message = this.$tc('easytranslate-project.detail.notification.sendProjectError');
                    if (error.response?.data?.includes("No content")) {
                        message = this.$tc('easytranslate-project.detail.notification.noContentError')
                    }
                    this.createNotificationError({ message });

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
                this.sourceLanguageIsEmpty = !this.project.sourceLanguageId;
                this.targetLanguagesIsEmpty = !this.project.targetLanguages || this.project.targetLanguages.length === 0;
                this.nameIsEmpty = !this.project.name || this.project.name.length === 0;
                this.workflowIsEmpty = !this.project.workflow || this.project.workflow.length === 0;

                if (this.project.categories.length === 0 && this.project.products.length === 0) {
                    this.createNotificationError({
                        message: this.$tc('easytranslate-project.detail.notification.noCategoriesAndProductsSaveError'),
                    });
                    return;
                }

                // Necessary as repository.save doesn't give errors when source or target is empty
                // and we want all errors to be shown at once, if possible.
                if (!this.nameIsEmpty && !this.workflowIsEmpty && !this.sourceLanguageIsEmpty && !this.targetLanguagesIsEmpty) {
                    this.createProject();
                }
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
            this.$router.push({name: 'easytranslate.project.index'});
        },
    },
});
