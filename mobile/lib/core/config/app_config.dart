class AppConfig {
  const AppConfig({required this.apiBaseUrl});

  factory AppConfig.fromEnvironment() {
    return const AppConfig(
      apiBaseUrl: String.fromEnvironment(
        'API_BASE_URL',
        defaultValue: 'http://10.0.2.2:8000/api/mobile/v1',
      ),
    );
  }

  final String apiBaseUrl;
}
