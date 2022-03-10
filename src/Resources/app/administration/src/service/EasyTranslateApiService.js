const ApiService = Shopware.Classes.ApiService;
const { Application } = Shopware;

class EasyTranslateApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'easytranslate/project') {
        super(httpClient, loginService, apiEndpoint);
    }

    sendProject(data) {
        const headers = this.getBasicHeaders({});

        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/sendProject`,
            data,
            {
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    handlePrice(data) {
        const headers = this.getBasicHeaders({});

        return this.httpClient.post(
            `_action/${this.getApiBasePath()}/handlePrice`,
            data,
            {
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

Application.addServiceProvider('easyTranslateApiService', (container) => {
    const initContainer = Application.getContainer('init');
    return new EasyTranslateApiService(initContainer.httpClient, container.loginService);
});
