const ApiService = Shopware.Classes.ApiService;
const { Application } = Shopware;

class EasyTranslateApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'easytranslate') {
        super(httpClient, loginService, apiEndpoint);
    }

    testConfig(data) {
        const headers = this.getBasicHeaders({});

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/verify`,
                data,
                headers
            ).then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    getWorkflowOptions() {
        const headers = this.getBasicHeaders({});

        return this.httpClient
            .get(
                `_action/${this.getApiBasePath()}/workflowOptions`,
                {
                    headers
                }
            ).then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    sendProject(data) {
        const headers = this.getBasicHeaders({});

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/project/sendProject`,
                data,
                headers
            ).then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    handlePrice(data) {
        const headers = this.getBasicHeaders({});

        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/project/handlePrice`,
            data,
            headers
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

Application.addServiceProvider('easyTranslateApiService', (container) => {
    const initContainer = Application.getContainer('init');
    return new EasyTranslateApiService(initContainer.httpClient, container.loginService);
});
