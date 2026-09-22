import 'package:flutter/material.dart';
import '../../constants/api_endpoints.dart';
import '../../constants/colors.dart';
import '../../models/user_model.dart';
import '../../providers/app_state.dart';
import '../../services/auth_service.dart';
import '../../widgets/custom_app_bar.dart';

class ProfileScreen extends StatefulWidget {
  final AppState? appState;

  const ProfileScreen({super.key, this.appState});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final TextEditingController _serverController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _serverController.text = ApiEndpoints.baseUrl;
  }

  @override
  void dispose() {
    _serverController.dispose();
    super.dispose();
  }

  void _saveServerConfig() async {
    final newUrl = _serverController.text.trim();
    if (newUrl.isNotEmpty) {
      ApiEndpoints.updateBaseUrl(newUrl);
      await AuthService.saveServerUrl(newUrl);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Server endpoint updated to: $newUrl'),
            backgroundColor: AppColors.emerald,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    // Access app state from widget or route context
    final appState = widget.appState ??
        ModalRoute.of(context)?.settings.arguments as AppState? ??
        (context.findAncestorStateOfType<State>() as dynamic)?.appState as AppState? ??
        AppState();

    final user = appState.currentUser;
    final activeRole = appState.activeRole;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        title: const Text(
          'Partner Profile & Settings',
          style: TextStyle(color: AppColors.textPrimary, fontSize: 16, fontWeight: FontWeight.bold),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            // User Avatar & Name Card
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: AppColors.cardBorder),
              ),
              child: Column(
                children: [
                  Container(
                    width: 76,
                    height: 76,
                    decoration: BoxDecoration(
                      gradient: AppColors.goldGradient,
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: AppColors.gold.withValues(alpha: 0.3),
                          blurRadius: 16,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Center(
                      child: Text(
                        user?.name.substring(0, 1).toUpperCase() ?? 'M',
                        style: const TextStyle(
                          color: Color(0xFF0F172A),
                          fontSize: 32,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),

                  Text(
                    user?.name ?? 'MY TAYLOR Partner',
                    style: const TextStyle(
                      color: AppColors.textPrimary,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    user?.mobile ?? '9800000000',
                    style: const TextStyle(
                      color: AppColors.textSecondary,
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(height: 10),

                  // Active Role Pill
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: activeRole == AppRole.measurementExecutive
                          ? AppColors.purple.withValues(alpha: 0.2)
                          : AppColors.amber.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: activeRole == AppRole.measurementExecutive ? AppColors.purple : AppColors.amber,
                      ),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          activeRole == AppRole.measurementExecutive ? Icons.straighten : Icons.delivery_dining,
                          size: 16,
                          color: activeRole == AppRole.measurementExecutive ? AppColors.purple : AppColors.amber,
                        ),
                        const SizedBox(width: 6),
                        Text(
                          activeRole.displayName,
                          style: TextStyle(
                            color: activeRole == AppRole.measurementExecutive ? AppColors.purple : AppColors.amber,
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Online Duty Status Toggle
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.cardBorder),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: appState.isOnDuty ? AppColors.emerald.withValues(alpha: 0.2) : AppColors.rose.withValues(alpha: 0.2),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      appState.isOnDuty ? Icons.radar : Icons.power_settings_new,
                      color: appState.isOnDuty ? AppColors.emerald : AppColors.rose,
                      size: 20,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          appState.isOnDuty ? 'On Field Duty (सक्रिय)' : 'Off Duty (ऑफ़लाइन)',
                          style: const TextStyle(
                            color: AppColors.textPrimary,
                            fontWeight: FontWeight.bold,
                            fontSize: 14,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        Text(
                          appState.isOnDuty ? 'Ready to receive visits & tasks' : 'Pausing new field tasks',
                          style: const TextStyle(color: AppColors.textSecondary, fontSize: 11),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Switch(
                    value: appState.isOnDuty,
                    activeTrackColor: AppColors.emerald,
                    onChanged: (_) {
                      setState(() {
                        appState.toggleDuty();
                      });
                    },
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Role Switcher Button
            InkWell(
              onTap: () => showRoleSwitchModal(context, appState),
              borderRadius: BorderRadius.circular(16),
              child: Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.gold.withValues(alpha: 0.4)),
                ),
                child: const Row(
                  children: [
                    Icon(Icons.swap_horizontal_circle, color: AppColors.gold, size: 24),
                    SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Switch Partner Mode / मोड बदलें',
                            style: TextStyle(
                              color: AppColors.textPrimary,
                              fontWeight: FontWeight.bold,
                              fontSize: 14,
                            ),
                          ),
                          Text(
                            'Toggle between Measurement Master & Delivery Boy',
                            style: TextStyle(color: AppColors.textMuted, fontSize: 12),
                          ),
                        ],
                      ),
                    ),
                    Icon(Icons.chevron_right, color: AppColors.textMuted),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Server Backend Settings
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.cardBorder),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.dns, color: AppColors.sky, size: 18),
                      SizedBox(width: 8),
                      Text(
                        'Backend Server Configuration',
                        style: TextStyle(
                          color: AppColors.textPrimary,
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    controller: _serverController,
                    style: const TextStyle(color: AppColors.textPrimary, fontSize: 12),
                    decoration: InputDecoration(
                      hintText: 'https://mytaylor.in/api',
                      hintStyle: const TextStyle(color: AppColors.textMuted, fontSize: 12),
                      filled: true,
                      fillColor: AppColors.inputBg,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                        borderSide: const BorderSide(color: AppColors.cardBorder),
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Align(
                    alignment: Alignment.centerRight,
                    child: TextButton.icon(
                      onPressed: _saveServerConfig,
                      icon: const Icon(Icons.save, size: 14, color: AppColors.gold),
                      label: const Text('Save Server URL', style: TextStyle(color: AppColors.gold, fontSize: 12)),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Logout Button
            OutlinedButton.icon(
              onPressed: () async {
                final nav = Navigator.of(context);
                await appState.logout();
                nav.pop();
              },
              icon: const Icon(Icons.power_settings_new, color: AppColors.rose, size: 18),
              label: const Text('LOGOUT FROM PARTNER APP', style: TextStyle(color: AppColors.rose, fontWeight: FontWeight.bold)),
              style: OutlinedButton.styleFrom(
                side: const BorderSide(color: AppColors.rose),
                padding: const EdgeInsets.symmetric(vertical: 14),
                minimumSize: const Size(double.infinity, 48),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
            const SizedBox(height: 30),
          ],
        ),
      ),
    );
  }
}
