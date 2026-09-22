import 'package:flutter/material.dart';
import '../constants/colors.dart';

class StatusBadge extends StatelessWidget {
  final String status;
  final bool isExpress;

  const StatusBadge({
    super.key,
    required this.status,
    this.isExpress = false,
  });

  @override
  Widget build(BuildContext context) {
    Color bg;
    Color fg;
    String label;
    IconData? icon;

    switch (status) {
      case 'BOOKED':
      case 'EXECUTIVE_ASSIGNED':
        bg = AppColors.sky.withValues(alpha: 0.15);
        fg = AppColors.sky;
        label = 'Assigned';
        icon = Icons.calendar_month;
        break;

      case 'EXECUTIVE_ON_THE_WAY':
        bg = AppColors.purple.withValues(alpha: 0.2);
        fg = AppColors.purple;
        label = 'On The Way';
        icon = Icons.directions_bike;
        break;

      case 'EXECUTIVE_ARRIVED':
        bg = AppColors.amber.withValues(alpha: 0.2);
        fg = AppColors.amber;
        label = 'Arrived';
        icon = Icons.location_on;
        break;

      case 'MEASUREMENT_STARTED':
        bg = AppColors.gold.withValues(alpha: 0.2);
        fg = AppColors.gold;
        label = 'In Progress';
        icon = Icons.straighten;
        break;

      case 'MEASUREMENT_COMPLETED':
      case 'DELIVERED':
        bg = AppColors.emerald.withValues(alpha: 0.2);
        fg = AppColors.emerald;
        label = status == 'DELIVERED' ? 'Delivered' : 'Completed';
        icon = Icons.check_circle;
        break;

      case 'READY_FOR_DISPATCH':
        bg = AppColors.purple.withValues(alpha: 0.2);
        fg = AppColors.purple;
        label = 'Ready for Pickup';
        icon = Icons.inventory_2;
        break;

      case 'OUT_FOR_DELIVERY':
        bg = AppColors.amber.withValues(alpha: 0.2);
        fg = AppColors.amber;
        label = 'Out for Delivery';
        icon = Icons.moped;
        break;

      default:
        bg = AppColors.surfaceLight;
        fg = AppColors.textSecondary;
        label = status.replaceAll('_', ' ');
        icon = Icons.info_outline;
        break;
    }

    return Wrap(
      spacing: 6,
      runSpacing: 4,
      crossAxisAlignment: WrapCrossAlignment.center,
      children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
          decoration: BoxDecoration(
            color: bg,
            borderRadius: BorderRadius.circular(6),
            border: Border.all(color: fg.withValues(alpha: 0.4), width: 1),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, size: 12, color: fg),
              const SizedBox(width: 4),
              Text(
                label,
                style: TextStyle(
                  color: fg,
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
        ),
        if (isExpress)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFFE11D48), Color(0xFFF43F5E)],
              ),
              borderRadius: BorderRadius.circular(6),
            ),
            child: const Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.bolt, color: Colors.white, size: 12),
                SizedBox(width: 2),
                Text(
                  '24H EXPRESS',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 10,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 0.5,
                  ),
                ),
              ],
            ),
          ),
      ],
    );
  }
}
