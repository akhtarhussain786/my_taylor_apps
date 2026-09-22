import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'constants/colors.dart';
import 'models/user_model.dart';
import 'providers/app_state.dart';
import 'screens/auth/login_screen.dart';
import 'screens/executive/executive_home_screen.dart';
import 'screens/delivery/delivery_home_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const MyTaylorPartnerApp());
}

class MyTaylorPartnerApp extends StatefulWidget {
  const MyTaylorPartnerApp({super.key});

  @override
  State<MyTaylorPartnerApp> createState() => _MyTaylorPartnerAppState();
}

class _MyTaylorPartnerAppState extends State<MyTaylorPartnerApp> {
  final AppState _appState = AppState();

  @override
  void initState() {
    super.initState();
    _appState.init();
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: _appState,
      builder: (context, _) {
        return MaterialApp(
          title: 'MY TAYLOR - Field Operations Partner',
          debugShowCheckedModeBanner: false,
          theme: ThemeData(
            useMaterial3: true,
            brightness: Brightness.dark,
            scaffoldBackgroundColor: AppColors.background,
            primaryColor: AppColors.gold,
            colorScheme: const ColorScheme.dark(
              primary: AppColors.gold,
              secondary: AppColors.goldLight,
              surface: AppColors.surface,
              surfaceContainerHighest: AppColors.surfaceLight,
              error: AppColors.rose,
            ),
            textTheme: GoogleFonts.outfitTextTheme(ThemeData.dark().textTheme),
            appBarTheme: const AppBarTheme(
              backgroundColor: AppColors.surface,
              foregroundColor: AppColors.textPrimary,
              elevation: 0,
            ),
            cardTheme: CardThemeData(
              color: AppColors.surface,
              elevation: 0,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
                side: const BorderSide(color: AppColors.cardBorder),
              ),
            ),
          ),
          home: _getHomeScreen(),
        );
      },
    );
  }

  Widget _getHomeScreen() {
    if (_appState.isLoading && _appState.currentUser == null) {
      return const Scaffold(
        backgroundColor: AppColors.background,
        body: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              CircularProgressIndicator(color: AppColors.gold),
              SizedBox(height: 16),
              Text(
                'Loading MY TAYLOR Field Partner...',
                style: TextStyle(color: AppColors.goldLight, fontSize: 13),
              ),
            ],
          ),
        ),
      );
    }

    if (!_appState.isAuthenticated) {
      return LoginScreen(appState: _appState);
    }

    // Role-based main screen
    if (_appState.activeRole == AppRole.deliveryExecutive) {
      return DeliveryHomeScreen(appState: _appState);
    } else {
      return ExecutiveHomeScreen(appState: _appState);
    }
  }
}
