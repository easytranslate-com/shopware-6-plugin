
const { Component, Mixin } = Shopware;
import template from './easytranslate-api-test-button.html.twig';

Component.register('easytranslate-api-test-button', {
    template: template,

    props: ['btnLabel'],
    inject: ['easyTranslateApiService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
        };
    },

    computed: {
        pluginConfig() {
            let config = null;
            let component = this;

            do {
                component = component.$parent;
                config = component.actualConfigData;
            } while (!config)

            const currentSalesChannelId = component.currentSalesChannelId;
            return config[currentSalesChannelId];
        }
    },

    methods: {
        saveFinish() {
            this.isSaveSuccessful = false;
        },

        testApi() {
            this.isLoading = true;
            this.easyTranslateApiService
                .testConfig(this.pluginConfig)
                .then((res) => {
                    if (res.isValid) {
                        this.isSaveSuccessful = true;
                        this.createNotificationSuccess({
                            title: this.$tc('easytranslate-api-test-button.title'),
                            message: this.$tc('easytranslate-api-test-button.success')
                        });
                    } else {
                        this.createNotificationError({
                            title: this.$tc('easytranslate-api-test-button.title'),
                            message: this.$tc('easytranslate-api-test-button.error')
                        });
                    }

                    setTimeout(() => {
                        this.isLoading = false;
                    }, 2500);
                })
                .catch(() => {
                    this.createNotificationError({
                        title: this.$tc('easytranslate-api-test-button.title'),
                        message: this.$tc('easytranslate-api-test-button.error')
                    });
                })
                .finally(() => {
                    this.isLoading = false;
                });
        }
    }
})
