import template from './easytranslate-project-list.html.twig';

const { Component, Mixin, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('easytranslate-project-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        'notification',
        Mixin.getByName('listing')
    ],

    data() {
        return {
            isLoading: false,
            total: 0,
            projects: null,
            sortBy: 'name',
            sortDirection: 'ASC',
        };
    },

    computed: {
        projectRepository() {
            return this.repositoryFactory.create('easytranslate_project');
        },

        projectCriteria() {
            const criteria = new Criteria(this.page, this.limit);

            criteria
                .addAssociation('sourceLanguage')
                .addAssociation('targetLanguages');

            return criteria;
        },

        projectColumns() {
            return [{
                property: 'name',
                label: this.$tc('easytranslate-project.list.columnName'),
                routerLink: 'easytranslate.project.detail',
                allowResize: true,
                primary: true,
            }, {
                property: 'translationPrice',
                label: this.$tc('easytranslate-project.list.columnTranslationPrice'),
                allowResize: true,
            }, {
                property: 'sourceLanguage.name',
                label: this.$tc('easytranslate-project.list.columnSourceLanguage'),
                allowResize: true,
            }, {
                property: 'targetLanguages',
                label: this.$tc('easytranslate-project.list.columnTargetLanguages'),
                allowResize: true,
                sortable: false,
            }, {
                property: 'status',
                label: this.$tc('easytranslate-project.list.columnStatus'),
                allowResize: true,
            }];
        },
    },

    methods: {
        getList() {
            this.isLoading = true;

            return this.projectRepository.search(this.projectCriteria, Context.api).then((projects) => {
                this.total = projects.total;
                this.projects = projects;

                return projects;
            }).catch((error) => {
                this.createNotificationError(error);
            }).finally(() => {
                this.isLoading = false;
            });
        },
    }
});

