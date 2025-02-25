<template>
  <div class="user-management">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>用户管理</span>
          <el-button type="primary" @click="showCreateDialog">创建用户</el-button>
        </div>
      </template>

      <el-table :data="users" v-loading="loading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="姓名" />
        <el-table-column prop="email" label="邮箱" />
        <el-table-column prop="phone" label="手机号" />
        <el-table-column prop="parent.name" label="上级用户" />
        <el-table-column prop="role.display_name" label="角色" />
        <el-table-column label="创建时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="120">
          <template #default="{ row }">
            <el-button @click="editUser(row)" type="text" size="small">编辑</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :total="total"
          @current-change="handlePageChange"
          layout="total, prev, pager, next"
        />
      </div>
    </el-card>

    <!-- 创建用户对话框 -->
    <el-dialog
      :visible.sync="dialogVisible"
      title="创建用户"
      width="500px"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="姓名" prop="name">
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="form.email" />
        </el-form-item>
        <el-form-item label="密码" prop="password">
          <el-input v-model="form.password" type="password" />
        </el-form-item>
        <el-form-item label="手机号" prop="phone">
          <el-input v-model="form.phone" />
        </el-form-item>
        <el-form-item label="上级用户" prop="parent_id">
          <el-select
            v-model="form.parent_id"
            clearable
            filterable
            placeholder="请选择上级用户"
          >
            <el-option
              v-for="user in userOptions"
              :key="user.id"
              :label="user.name"
              :value="user.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="角色" prop="role_id">
          <el-select
            v-model="form.role_id"
            placeholder="请选择角色"
            required
          >
            <el-option
              v-for="role in roleOptions"
              :key="role.id"
              :label="role.display_name"
              :value="role.id"
            />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">取消</el-button>
          <el-button type="primary" @click="submitForm">确定</el-button>
        </span>
      </template>
    </el-dialog>

    <el-dialog
      :visible.sync="dialogVisible"
      title="编辑用户"
      width="500px"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="姓名" prop="name">
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="form.email" />
        </el-form-item>
        <el-form-item label="密码" prop="password">
          <el-input v-model="form.password" type="password" />
        </el-form-item>
        <el-form-item label="手机号" prop="phone">
          <el-input v-model="form.phone" />
        </el-form-item>
        <el-form-item label="上级用户" prop="parent_id">
          <el-select
            v-model="form.parent_id"
            clearable
            filterable
            placeholder="请选择上级用户"
          >
            <el-option
              v-for="user in userOptions"
              :key="user.id"
              :label="user.name"
              :value="user.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="角色" prop="role_id">
          <el-select
            v-model="form.role_id"
            placeholder="请选择角色"
            required
          >
            <el-option
              v-for="role in roleOptions"
              :key="role.id"
              :label="role.display_name"
              :value="role.id"
            />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">取消</el-button>
          <el-button type="primary" @click="updateUser">确定</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import axios from 'axios'
export default {
  data() {
    return {
      users: [],
      loading: false,
      currentPage: 1,
      pageSize: 10,
      total: 0,
      dialogVisible: false,
      userOptions: [],
      roleOptions: [],
      form: {
        id: null,
        name: '',
        email: '',
        password: '',
        phone: '',
        parent_id: null,
        role_id: null
      },
      rules: {
        name: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
        email: [
          { required: true, message: '请输入邮箱', trigger: 'blur' },
          { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
        ],
        password: [
          { required: true, message: '请输入密码', trigger: 'blur' },
          { min: 8, message: '密码长度不能小于8位', trigger: 'blur' }
        ],
        phone: [
          { pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号', trigger: 'blur' }
        ],
        role_id: [
          { required: true, message: '请选择角色', trigger: 'change' }
        ]
      }
    }
  },

  methods: {
    async fetchUsers() {
      this.loading = true
      try {
        const response = await axios.get(`/api/users?page=${this.currentPage}`)
        this.users = response.data.data.data
        this.total = response.data.data.total
      } catch (error) {
        this.$message.error('获取用户列表失败')
      } finally {
        this.loading = false
      }
    },

    async fetchUserOptions() {
      try {
        const response = await axios.get('/api/users?page=1&per_page=100')
        this.userOptions = response.data.data.data
      } catch (error) {
        this.$message.error('获取用户选项失败')
      }
    },

    async fetchRoleOptions() {
      try {
        const response = await axios.get('/api/roles')
        this.roleOptions = response.data.data
      } catch (error) {
        this.$message.error('获取角色列表失败')
      }
    },

    handlePageChange(page) {
      this.currentPage = page
      this.fetchUsers()
    },

    showCreateDialog() {
      this.dialogVisible = true
      this.fetchUserOptions()
      this.fetchRoleOptions()
    },

    async submitForm() {
      if (!this.$refs.formRef) return
      
      this.$refs.formRef.validate(async (valid) => {
        if (valid) {
          try {
            await axios.post('/api/users', this.form)
            this.$message.success('创建用户成功')
            this.dialogVisible = false
            this.fetchUsers()
            this.$refs.formRef.resetFields()
          } catch (error) {
            this.$message.error(error.response?.data?.message || '创建用户失败')
          }
        }
      })
    },

    formatDate(date) {
      return new Date(date).toLocaleString()
    },

    editUser(user) {
      this.form = { ...user };
      this.dialogVisible = true;
    },

    async updateUser() {
      if (!this.$refs.formRef) return
      
      this.$refs.formRef.validate(async (valid) => {
        if (valid) {
          try {
            await axios.put(`/api/users/${this.form.id}`, this.form);
            this.$message.success('用户更新成功');
            this.dialogVisible = false;
            this.fetchUsers();
            this.$refs.formRef.resetFields();
          } catch (error) {
            this.$message.error(error.response?.data?.message || '更新用户失败');
          }
        }
      });
    }
  },

  mounted() {
    this.fetchUsers()
  }
}
</script>

<style scoped>
.user-management {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
</style> 